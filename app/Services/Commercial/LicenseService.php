<?php

namespace App\Services\Commercial;

use App\Models\Branch;
use App\Models\CommercialLicenseEvent;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\EdgeConfiguration;
use App\Models\License;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\Edge\HardwareFingerprintCollector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LicenseService
{
    public function __construct(private readonly LicenseLeaseService $leaseService) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{license: License, license_key: string}
     */
    public function issue(Organization $organization, array $attributes = []): array
    {
        $licenseKey = $this->generateLicenseKey();
        $normalizedKey = $this->normalizeLicenseKey($licenseKey);

        $plan = null;
        if (! empty($attributes['plan_id'])) {
            $plan = Plan::query()->find($attributes['plan_id']);
        }
        if (! $plan && ! empty($attributes['plan'])) {
            $plan = Plan::query()->where('code', $attributes['plan'])->first();
        }
        if (! $plan) {
            $plan = Plan::query()->active()->first();
        }

        $entitlementOverrides = $attributes['entitlement_overrides'] ?? null;
        $limitOverrides = $attributes['limit_overrides'] ?? null;

        if ($plan) {
            $overridesFeatures = $entitlementOverrides;
            if ($overridesFeatures === null && isset($attributes['features'])) {
                $overridesFeatures = $attributes['features'];
            }
            $overridesLimits = $limitOverrides;
            if ($overridesLimits === null && (isset($attributes['max_devices']) || isset($attributes['max_branches']))) {
                $overridesLimits = array_filter([
                    'max_devices' => $attributes['max_devices'] ?? null,
                    'max_branches' => $attributes['max_branches'] ?? null,
                ]);
            }

            $resolved = app(PlanService::class)->resolveEffectiveConfiguration(
                $plan,
                $overridesFeatures,
                $overridesLimits,
            );
            $planId = $plan->getKey();
            $planCode = $plan->code;
            $planVersion = $plan->version;
            $features = $resolved['features'];
            $maxDevices = $resolved['max_devices'];
            $maxBranches = $resolved['max_branches'];
        } else {
            $planId = null;
            $planCode = $attributes['plan'] ?? 'default';
            $planVersion = 1;
            $features = app(EntitlementService::class)->normalize($attributes['features'] ?? config('commercial.default_entitlements'));
            $maxDevices = max(1, (int) ($attributes['max_devices'] ?? 1));
            $maxBranches = max(1, (int) ($attributes['max_branches'] ?? 1));
        }

        $license = $organization->licenses()->create([
            'key_hash' => Hash::make($normalizedKey),
            'key_fingerprint' => hash('sha256', $normalizedKey),
            'key_last_four' => substr($normalizedKey, -4),
            'plan_id' => $planId,
            'plan' => $planCode,
            'plan_code' => $planCode,
            'plan_version' => $planVersion,
            'status' => $attributes['status'] ?? 'active',
            'max_devices' => $maxDevices,
            'max_branches' => $maxBranches,
            'starts_at' => $attributes['starts_at'] ?? now(),
            'expires_at' => $attributes['expires_at'] ?? null,
            'grace_period_days' => $attributes['grace_period_days'] ?? 14,
            'features' => $features,
            'entitlement_overrides' => $entitlementOverrides,
            'limit_overrides' => $limitOverrides,
            'metadata' => $attributes['metadata'] ?? [],
        ]);

        return ['license' => $license, 'license_key' => $licenseKey];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{activation: DeviceActivation, activation_token: string}
     */
    public function activate(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $license = $this->findLicenseByKey($data['license_key'], lock: true);

            if (! $license->isActiveAt()) {
                throw new HttpException(403, __('api.license_not_active'));
            }

            $branch = null;
            if (! empty($data['branch_id'])) {
                $branch = Branch::query()->whereKey($data['branch_id'])->first();
                if (! $branch || $branch->organization_id !== $license->organization_id) {
                    throw ValidationException::withMessages([
                        'branch_id' => [__('api.license_branch_mismatch')],
                    ]);
                }
            } else {
                $branch = $license->organization->branches()->where('is_active', true)->first();
                if (! $branch) {
                    $branch = Branch::query()->firstOrCreate(
                        ['organization_id' => $license->organization_id, 'code' => 'MAIN'],
                        [
                            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
                            'currency' => $license->organization->currency ?? 'EGP',
                            'timezone' => $license->organization->timezone ?? 'Africa/Cairo',
                            'is_active' => true,
                        ]
                    );
                }
            }

            $activation = DeviceActivation::query()
                ->where('installation_id', $data['installation_id'])
                ->lockForUpdate()
                ->first();

            if ($activation && $activation->license_id !== $license->getKey()) {
                throw new ConflictHttpException(__('api.installation_already_activated'));
            }

            if (! $activation && $license->activations()->where('status', 'active')->count() >= $license->max_devices) {
                throw new ConflictHttpException(__('api.license_device_limit_reached'));
            }

            $device = $this->resolveDevice($activation, $branch, $data);
            $now = now();
            $metadata = [
                ...($data['metadata'] ?? []),
                'platform' => $data['platform'] ?? 'windows',
                'app_version' => $data['app_version'] ?? null,
            ];

            if (! $activation) {
                $activation = DeviceActivation::create([
                    'license_id' => $license->getKey(),
                    'device_id' => $device->getKey(),
                    'installation_id' => $data['installation_id'],
                    'activation_token_hash' => Hash::make(Str::random(64)),
                    'status' => 'active',
                    'activated_at' => $now,
                    'last_checkin_at' => $now,
                    'offline_grace_expires_at' => $now->copy()->addDays($license->grace_period_days),
                    'metadata' => $metadata,
                ]);
            } else {
                $activation->update([
                    'device_id' => $device->getKey(),
                    'status' => 'active',
                    'last_checkin_at' => $now,
                    'offline_grace_expires_at' => $now->copy()->addDays($license->grace_period_days),
                    'revoked_at' => null,
                    'metadata' => $metadata,
                ]);
            }

            $activationToken = $this->rotateActivationToken($activation);
            $device->forceFill([
                'name' => $data['device_name'] ?? 'Main Register',
                'type' => $data['device_type'] ?? 'pos',
                'metadata' => [
                    ...($device->metadata ?? []),
                    'installation_id' => $data['installation_id'],
                    ...$metadata,
                ],
            ])->saveQuietly();

            $configuration = EdgeConfiguration::query()
                ->where('installation_id', $activation->installation_id)
                ->first();

            $hardwareInfo = app(HardwareFingerprintCollector::class)->collect($data['platform'] ?? null);

            if (! $configuration) {
                $configuration = EdgeConfiguration::create([
                    'installation_id' => $activation->installation_id,
                    'organization_id' => $license->organization_id,
                    'branch_id' => $branch->getKey(),
                    'device_id' => $device->getKey(),
                    'edge_name' => $device->name ?: 'Pilot Edge',
                    'listen_address' => config('edge.listen_address', '127.0.0.1'),
                    'listen_port' => config('edge.listen_port', 8000),
                    'cloud_endpoint' => config('edge.cloud_endpoint', 'https://pos.pilot.test/api/v1'),
                    'database_driver' => config('database.default', 'sqlite'),
                    'data_path' => config('edge.paths.data', storage_path('edge/data')),
                    'log_level' => 'info',
                    'setup_completed_at' => null,
                    'metadata' => [
                        'installation_fingerprint' => $hardwareInfo['fingerprint'] ?? null,
                        'hardware_fingerprint' => $hardwareInfo ?? null,
                    ],
                ]);
            } elseif (! $configuration->organization_id || ! $configuration->branch_id) {
                $configuration->update([
                    'organization_id' => $license->organization_id,
                    'branch_id' => $branch->getKey(),
                    'device_id' => $device->getKey(),
                    'metadata' => [
                        ...($configuration->metadata ?? []),
                        'installation_fingerprint' => $hardwareInfo['fingerprint'] ?? null,
                        'hardware_fingerprint' => $hardwareInfo ?? null,
                    ],
                ]);
            }

            $lease = null;
            if ($configuration && (config('edge.mode') === 'edge-production' || app()->environment('testing', 'local'))) {
                $lease = $this->leaseService->issueAndPersist($activation->fresh(['license', 'device']), $configuration);
            }

            return [
                'activation' => $activation->fresh(['license', 'device']),
                'activation_token' => $activationToken,
                'lease' => $lease,
            ];
        });
    }

    public function resolveActivationToken(?string $token): ?DeviceActivation
    {
        if (! $token || ! str_contains($token, '.')) {
            return null;
        }

        [$activationId] = explode('.', $token, 2);
        $activation = DeviceActivation::query()
            ->whereKey($activationId)
            ->where('status', 'active')
            ->whereHas('device', fn ($query) => $query->where('is_active', true))
            ->with(['license', 'device.branch'])
            ->first();

        return $activation && Hash::check($token, $activation->activation_token_hash)
            ? $activation
            : null;
    }

    public function heartbeat(DeviceActivation $activation): DeviceActivation
    {
        $activation->loadMissing('license');
        $now = now();
        $data = ['last_checkin_at' => $now];

        if ($activation->license->isActiveAt($now)) {
            $data['offline_grace_expires_at'] = $now->copy()->addDays($activation->license->grace_period_days);
        }

        $activation->update($data);

        return $activation->fresh(['license', 'device']);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(DeviceActivation $activation): array
    {
        $activation->loadMissing(['license', 'device']);
        $license = $activation->license;
        $isOnlineUsable = $activation->status === 'active' && $license->isActiveAt();
        $metadata = $activation->metadata ?? [];

        $license->loadMissing(['commercialPlan', 'organization.branches']);
        $planModel = $license->planModel();
        $activeDevicesCount = $license->activations()->where('status', 'active')->count();
        $activeBranchesCount = $license->organization ? $license->organization->branches()->where('is_active', true)->count() : 0;

        return [
            'can_work_online' => $isOnlineUsable,
            'offline_grace_active' => $activation->status === 'active'
                && $activation->offline_grace_expires_at?->isFuture(),
            'license' => [
                'id' => $license->getKey(),
                'plan_id' => $license->plan_id,
                'plan' => $license->plan_code ?: $license->plan,
                'plan_code' => $license->plan_code ?: $license->plan,
                'plan_version' => $license->plan_version ?? $planModel?->version ?? 1,
                'plan_name' => $planModel?->name,
                'status' => $this->effectiveLicenseStatus($license),
                'max_devices' => $license->max_devices,
                'max_branches' => $license->max_branches,
                'active_devices_count' => $activeDevicesCount,
                'active_branches_count' => $activeBranchesCount,
                'is_over_limit_devices' => $activeDevicesCount > $license->max_devices,
                'is_over_limit_branches' => $activeBranchesCount > $license->max_branches,
                'expires_at' => $license->expires_at?->toISOString(),
                'features' => app(EntitlementService::class)->normalize($license->features),
                'entitlement_overrides' => $license->entitlement_overrides,
                'limit_overrides' => $license->limit_overrides,
            ],
            'activation' => [
                'id' => $activation->getKey(),
                'status' => $activation->status,
                'installation_id' => $activation->installation_id,
                'activated_at' => $activation->activated_at?->toISOString(),
                'last_checkin_at' => $activation->last_checkin_at?->toISOString(),
                'offline_grace_expires_at' => $activation->offline_grace_expires_at?->toISOString(),
                'revoked_at' => $activation->revoked_at?->toISOString(),
                'fingerprint_masked' => $metadata['hardware_fingerprint']['masked'] ?? null,
                'recovery_challenge' => ! empty($metadata['recovery_challenge']['expires_at']) && empty($metadata['recovery_challenge']['used_at']) ? [
                    'token_preview' => $metadata['recovery_challenge']['token_preview'] ?? null,
                    'reason' => $metadata['recovery_challenge']['reason'] ?? null,
                    'expires_at' => $metadata['recovery_challenge']['expires_at'] ?? null,
                    'is_expired' => now()->greaterThan($metadata['recovery_challenge']['expires_at']),
                ] : null,
                'replacement' => $metadata['replacement'] ?? null,
            ],
            'device' => [
                'id' => $activation->device->getKey(),
                'name' => $activation->device->name,
                'type' => $activation->device->type,
                'branch_id' => $activation->device->branch_id,
            ],
        ];
    }

    public function authorizeReplacement(DeviceActivation $activation, string $reason, ?User $actor = null): array
    {
        if ($activation->status !== 'active') {
            throw new HttpException(422, __('api.cannot_replace_inactive_activation'));
        }

        $rawToken = 'ROT-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        $normalizedToken = str_replace('-', '', strtoupper($rawToken));
        $tokenHash = hash('sha256', $normalizedToken);
        $expiresAt = now()->addMinutes(60);

        $metadata = $activation->metadata ?? [];
        $metadata['recovery_challenge'] = [
            'token_hash' => $tokenHash,
            'token_preview' => substr($rawToken, 0, 8).'••••',
            'reason' => $reason,
            'authorized_by_id' => $actor?->getKey(),
            'authorized_by_name' => $actor?->name ?? 'Admin',
            'authorized_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'used_at' => null,
        ];

        $activation->update(['metadata' => $metadata]);

        CommercialLicenseEvent::create([
            'license_id' => $activation->license_id,
            'device_activation_id' => $activation->getKey(),
            'event' => 'REPLACEMENT_AUTHORIZED',
            'metadata' => [
                'reason' => $reason,
                'token_preview' => substr($rawToken, 0, 8).'••••',
                'authorized_by' => $actor?->name ?? 'Admin',
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            'occurred_at' => now(),
        ]);

        return [
            'token' => $rawToken,
            'expires_at' => $expiresAt,
            'activation' => $activation->fresh(['license', 'device']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{activation: DeviceActivation, activation_token: string, lease: array<string, mixed>|null, old_installation_id: string}
     */
    public function recoverInstallation(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $license = $this->findLicenseByKey($data['license_key'], lock: true);

            if (! $license->isActiveAt()) {
                throw new HttpException(403, __('api.license_not_active'));
            }

            $normalizedToken = str_replace(['-', ' '], '', strtoupper($data['recovery_token'] ?? ''));
            if (strlen($normalizedToken) < 8) {
                throw ValidationException::withMessages([
                    'recovery_token' => [__('api.invalid_recovery_token')],
                ]);
            }
            $tokenHash = hash('sha256', $normalizedToken);

            $oldActivation = $license->activations()
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->first(function (DeviceActivation $candidate) use ($tokenHash): bool {
                    $challenge = $candidate->metadata['recovery_challenge'] ?? null;
                    if (! $challenge || empty($challenge['token_hash'])) {
                        return false;
                    }

                    return hash_equals($challenge['token_hash'], $tokenHash);
                });

            if (! $oldActivation) {
                throw ValidationException::withMessages([
                    'recovery_token' => [__('api.invalid_recovery_token')],
                ]);
            }

            $challenge = $oldActivation->metadata['recovery_challenge'] ?? [];
            if (! empty($challenge['used_at'])) {
                throw ValidationException::withMessages([
                    'recovery_token' => [__('api.recovery_token_already_used')],
                ]);
            }

            if (empty($challenge['expires_at']) || now()->greaterThan(Carbon::parse($challenge['expires_at']))) {
                throw ValidationException::withMessages([
                    'recovery_token' => [__('api.recovery_token_expired')],
                ]);
            }

            $newInstallationId = $data['installation_id'];

            // Mark old activation as replaced
            $oldMetadata = $oldActivation->metadata ?? [];
            $challenge['used_at'] = now()->toIso8601String();
            $oldMetadata['recovery_challenge'] = $challenge;
            $oldMetadata['replacement'] = [
                'replaced_by_installation_id' => $newInstallationId,
                'replaced_at' => now()->toIso8601String(),
                'reason' => $challenge['reason'] ?? 'Authorized Hardware Replacement',
            ];

            $oldActivation->update([
                'status' => 'replaced',
                'revoked_at' => now(),
                'metadata' => $oldMetadata,
            ]);

            $branch = $oldActivation->device->branch;
            $hardwareInfo = app(HardwareFingerprintCollector::class)->collect($data['platform'] ?? null);

            $newMetadata = [
                ...($data['metadata'] ?? []),
                'platform' => $data['platform'] ?? 'windows',
                'app_version' => $data['app_version'] ?? null,
                'hardware_fingerprint' => $hardwareInfo,
                'recovered_from_installation_id' => $oldActivation->installation_id,
                'recovered_at' => now()->toIso8601String(),
            ];

            // Create new Device for replacement host preserving historical device references
            $device = Device::create([
                'branch_id' => $branch->getKey(),
                'name' => $data['device_name'] ?? $oldActivation->device->name,
                'type' => $data['device_type'] ?? $oldActivation->device->type ?? 'pos',
                'metadata' => [
                    'installation_id' => $newInstallationId,
                    'hardware_fingerprint' => $hardwareInfo,
                    'recovered_from_device_id' => $oldActivation->device_id,
                ],
                'is_active' => true,
            ]);

            $newActivation = DeviceActivation::query()
                ->where('installation_id', $newInstallationId)
                ->first();

            $now = now();
            if (! $newActivation) {
                $newActivation = DeviceActivation::create([
                    'license_id' => $license->getKey(),
                    'device_id' => $device->getKey(),
                    'installation_id' => $newInstallationId,
                    'activation_token_hash' => Hash::make(Str::random(64)),
                    'status' => 'active',
                    'activated_at' => $now,
                    'last_checkin_at' => $now,
                    'offline_grace_expires_at' => $now->copy()->addDays($license->grace_period_days),
                    'metadata' => $newMetadata,
                ]);
            } else {
                $newActivation->update([
                    'license_id' => $license->getKey(),
                    'device_id' => $device->getKey(),
                    'status' => 'active',
                    'last_checkin_at' => $now,
                    'offline_grace_expires_at' => $now->copy()->addDays($license->grace_period_days),
                    'revoked_at' => null,
                    'metadata' => $newMetadata,
                ]);
            }

            $activationToken = $this->rotateActivationToken($newActivation);

            $configuration = EdgeConfiguration::query()
                ->where('installation_id', $newActivation->installation_id)
                ->first();
            if ($configuration) {
                $configuration->update([
                    'organization_id' => $license->organization_id,
                    'branch_id' => $branch->getKey(),
                    'device_id' => $device->getKey(),
                    'metadata' => [
                        ...($configuration->metadata ?? []),
                        'installation_fingerprint' => $hardwareInfo['fingerprint'],
                        'hardware_fingerprint' => $hardwareInfo,
                    ],
                ]);
            }

            $lease = null;
            if ($configuration && (config('edge.mode') === 'edge-production' || app()->environment('testing', 'local'))) {
                $lease = $this->leaseService->issueAndPersist($newActivation->fresh(['license', 'device']), $configuration);
            }

            CommercialLicenseEvent::create([
                'license_id' => $license->getKey(),
                'device_activation_id' => $newActivation->getKey(),
                'event' => 'INSTALLATION_REPLACED',
                'metadata' => [
                    'old_installation_id' => $oldActivation->installation_id,
                    'new_installation_id' => $newInstallationId,
                    'reason' => $challenge['reason'] ?? 'Hardware Replacement',
                    'fingerprint' => $hardwareInfo['masked'],
                ],
                'occurred_at' => now(),
            ]);

            return [
                'activation' => $newActivation->fresh(['license', 'device']),
                'activation_token' => $activationToken,
                'lease' => $lease,
                'old_installation_id' => $oldActivation->installation_id,
            ];
        });
    }

    public function deactivateInstallation(DeviceActivation $activation, ?string $reason = null): void
    {
        $activation->update([
            'status' => 'deactivated',
            'revoked_at' => now(),
            'metadata' => [
                ...($activation->metadata ?? []),
                'deactivation_reason' => $reason,
                'deactivated_at' => now()->toIso8601String(),
            ],
        ]);

        CommercialLicenseEvent::create([
            'license_id' => $activation->license_id,
            'device_activation_id' => $activation->getKey(),
            'event' => 'INSTALLATION_DEACTIVATED',
            'metadata' => [
                'installation_id' => $activation->installation_id,
                'reason' => $reason,
            ],
            'occurred_at' => now(),
        ]);
    }

    private function resolveDevice(?DeviceActivation $activation, Branch $branch, array $data): Device
    {
        if ($activation) {
            $device = Device::query()->findOrFail($activation->device_id);
        } elseif ($data['device_id'] ?? null) {
            $device = Device::query()->findOrFail($data['device_id']);
        } else {
            return Device::create([
                'branch_id' => $branch->getKey(),
                'name' => $data['device_name'],
                'type' => $data['device_type'] ?? 'pos',
                'is_active' => true,
            ]);
        }

        if (! $device->is_active || $device->branch_id !== $branch->getKey()) {
            throw ValidationException::withMessages([
                'device_id' => [__('api.license_device_mismatch')],
            ]);
        }

        return $device;
    }

    private function rotateActivationToken(DeviceActivation $activation): string
    {
        $token = $activation->getKey().'.'.Str::random(64);
        $activation->update([
            'activation_token_hash' => Hash::make($token),
            'activation_credential' => $token,
        ]);

        return $token;
    }

    private function findLicenseByKey(string $licenseKey, bool $lock = false): License
    {
        $normalizedKey = $this->normalizeLicenseKey($licenseKey);
        $query = License::query()->where('key_fingerprint', hash('sha256', $normalizedKey));

        if ($lock) {
            $query->lockForUpdate();
        }

        $license = $query->first();
        if (! $license || ! Hash::check($normalizedKey, $license->key_hash)) {
            throw ValidationException::withMessages([
                'license_key' => [__('api.invalid_license_key')],
            ]);
        }

        return $license;
    }

    private function generateLicenseKey(): string
    {
        return 'POS-'.collect(range(1, 4))
            ->map(fn (): string => Str::upper(Str::random(4)))
            ->implode('-');
    }

    private function normalizeLicenseKey(string $licenseKey): string
    {
        return Str::upper(preg_replace('/[^A-Z0-9]/i', '', $licenseKey) ?? '');
    }

    private function effectiveLicenseStatus(License $license): string
    {
        if ($license->status === 'active' && $license->expires_at?->isPast()) {
            return 'expired';
        }

        return $license->status;
    }
}
