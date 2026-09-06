<?php

namespace App\Http\Resources\Commercial;

use App\Models\Organization;
use App\Services\Commercial\EntitlementService;
use App\Services\Edge\HardwareFingerprintCollector;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommercialOrganizationDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Organization $org */
        $org = $this->resource;
        $entitlementService = app(EntitlementService::class);
        $fingerprintCollector = app(HardwareFingerprintCollector::class);

        $activeLicense = $org->licenses->firstWhere('status', 'active') ?? $org->licenses->sortByDesc('created_at')->first();

        $activeDevicesCount = $org->branches->flatMap->devices->where('is_active', true)->count();
        $activeBranchesCount = $org->branches->where('is_active', true)->count();

        $latestDevice = $org->branches->flatMap->devices->sortByDesc('last_seen_at')->first();
        $lastSeenAt = $latestDevice?->last_seen_at;
        $heartbeatStatus = 'OFFLINE';
        if ($lastSeenAt) {
            $diffSeconds = now()->diffInSeconds($lastSeenAt);
            if ($diffSeconds <= 90) {
                $heartbeatStatus = 'ACTIVE';
            } elseif ($diffSeconds <= 600) {
                $heartbeatStatus = 'STALE';
            }
        }

        // Activations & Devices
        $activationsData = [];
        if ($activeLicense) {
            foreach ($activeLicense->activations as $activation) {
                $device = $activation->device;
                $branch = $device?->branch;
                $fingerprint = data_get($activation->metadata, 'fingerprint.components')
                    ? $fingerprintCollector->generate(data_get($activation->metadata, 'fingerprint.components'))['fingerprint']
                    : null;

                $activationsData[] = [
                    'id' => $activation->getKey(),
                    'status' => $activation->status,
                    'installation_id' => $activation->installation_id,
                    'device_id' => $activation->device_id,
                    'device_name' => $device?->name ?? 'Unknown Device',
                    'device_type' => $device?->type ?? 'pos',
                    'branch_id' => $device?->branch_id,
                    'branch_name' => $branch?->name,
                    'activated_at' => $activation->activated_at?->toISOString(),
                    'last_checkin_at' => $activation->last_checkin_at?->toISOString(),
                    'offline_grace_expires_at' => $activation->offline_grace_expires_at?->toISOString(),
                    'fingerprint_masked' => $fingerprint ? $fingerprintCollector->mask($fingerprint) : null,
                    'recovery_challenge' => data_get($activation->metadata, 'recovery_challenge') ? [
                        'token_preview' => 'ROT-••••••••',
                        'reason' => data_get($activation->metadata, 'recovery_challenge.reason'),
                        'expires_at' => data_get($activation->metadata, 'recovery_challenge.expires_at'),
                        'is_expired' => data_get($activation->metadata, 'recovery_challenge.expires_at') ? CarbonImmutable::parse(data_get($activation->metadata, 'recovery_challenge.expires_at'))->isPast() : true,
                    ] : null,
                ];
            }
        }

        // Licenses History
        $licensesHistory = [];
        foreach ($org->licenses->sortByDesc('created_at') as $lic) {
            $now = CarbonImmutable::now();
            $expiresAt = $lic->expires_at ? CarbonImmutable::parse($lic->expires_at) : null;

            $licensesHistory[] = [
                'id' => $lic->getKey(),
                'status' => $lic->status,
                'plan_id' => $lic->plan_id,
                'plan_code' => $lic->plan_code ?: $lic->plan,
                'plan_version' => $lic->plan_version,
                'plan_name' => $lic->commercialPlan?->name,
                'key_last_four' => $lic->key_last_four,
                'starts_at' => $lic->starts_at?->toISOString(),
                'expires_at' => $lic->expires_at?->toISOString(),
                'grace_period_days' => $lic->grace_period_days,
                'max_devices' => $lic->max_devices,
                'max_branches' => $lic->max_branches,
                'features' => $entitlementService->normalize($lic->features),
                'entitlement_overrides' => $lic->entitlement_overrides,
                'limit_overrides' => $lic->limit_overrides,
                'is_active' => $lic->status === 'active',
                'days_remaining' => $expiresAt ? (int) $now->diffInDays($expiresAt, false) : null,
                'is_expired' => $expiresAt ? $expiresAt->isPast() : false,
                'activations_count' => $lic->activations->count(),
                'created_at' => $lic->created_at?->toISOString(),
            ];
        }

        // Branches summary
        $branchesData = $org->branches->map(fn ($b) => [
            'id' => $b->getKey(),
            'name' => $b->name,
            'code' => $b->code,
            'is_active' => (bool) $b->is_active,
            'devices_count' => $b->devices->where('is_active', true)->count(),
            'created_at' => $b->created_at?->toISOString(),
        ])->values()->all();

        $hasActivations = $activeLicense && $activeLicense->activations->where('status', 'active')->isNotEmpty();
        $derivedStatus = 'PROVISIONED';
        if ($org->onboarding_status === 'COMPLETED') {
            $derivedStatus = $heartbeatStatus === 'ACTIVE' ? 'OPERATIONAL' : 'SETUP_COMPLETED';
        } elseif ($org->onboarding_status === 'IN_PROGRESS') {
            $derivedStatus = 'SETUP_IN_PROGRESS';
        } elseif ($hasActivations) {
            $derivedStatus = 'ACTIVATED';
        }

        return [
            'id' => $org->getKey(),
            'name' => $org->name,
            'slug' => $org->slug,
            'currency' => $org->currency,
            'timezone' => $org->timezone,
            'contact_name' => data_get($org->settings, 'contact_name'),
            'contact_phone' => data_get($org->settings, 'contact_phone'),
            'contact_email' => data_get($org->settings, 'contact_email'),
            'notes' => data_get($org->settings, 'notes'),
            'onboarding_status' => $org->onboarding_status ?? 'NOT_STARTED',
            'onboarding_step' => $org->onboarding_step,
            'onboarding_completed_steps' => $org->onboarding_completed_steps ?? [],
            'onboarding_completed_at' => $org->onboarding_completed_at?->toISOString(),
            'is_active' => (bool) $org->is_active,
            'active_devices_count' => $activeDevicesCount,
            'active_branches_count' => $activeBranchesCount,
            'last_seen_at' => $lastSeenAt?->toISOString(),
            'heartbeat_status' => $heartbeatStatus,
            'derived_status' => $derivedStatus,
            'active_license' => $licensesHistory[0] ?? null,
            'licenses' => $licensesHistory,
            'activations' => $activationsData,
            'branches' => $branchesData,
            'created_at' => $org->created_at?->toISOString(),
            'updated_at' => $org->updated_at?->toISOString(),
        ];
    }
}
