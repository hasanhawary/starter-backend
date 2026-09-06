<?php

namespace App\Services\Commercial;

use App\Models\CommercialLicenseEvent;
use App\Models\DeviceActivation;
use App\Models\EdgeConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseLeaseService
{
    public function __construct(
        private readonly EntitlementService $entitlementService,
        private readonly LicenseLeaseStore $store,
        private readonly LicenseLeaseVerifier $verifier,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function issue(DeviceActivation $activation, ?CarbonImmutable $issuedAt = null): array
    {
        $activation->loadMissing(['license.organization', 'device.branch']);
        $license = $activation->license;
        $issuedAt ??= CarbonImmutable::now();
        $duration = max(60, (int) config('commercial.license.lease_duration_seconds'));
        $configuredExpiry = $license->expires_at ? CarbonImmutable::instance($license->expires_at) : null;
        $expiresAt = $issuedAt->addSeconds($duration);
        if ($configuredExpiry && $configuredExpiry->lessThan($expiresAt)) {
            $expiresAt = $configuredExpiry;
        }

        $graceUntil = $expiresAt->addDays(max(0, (int) $license->grace_period_days));
        $deviceType = Str::upper((string) ($activation->device?->type ?: 'edge'));
        $payload = [
            'lease_id' => (string) Str::uuid(),
            'license_id' => (string) $license->getKey(),
            'organization_id' => (string) $license->organization_id,
            'branch_id' => (string) $activation->device?->branch_id,
            'device_id' => (string) $activation->device_id,
            'installation_id' => $activation->installation_id,
            'device_type' => $deviceType,
            'license_status' => Str::upper((string) $license->status),
            'plan' => Str::upper((string) ($license->plan_code ?: $license->plan ?: 'STANDARD')),
            'entitlements' => $this->entitlementService->normalize($license->features),
            'issued_at' => $issuedAt->toISOString(),
            'not_before' => $issuedAt->subSeconds((int) config('commercial.license.clock_tolerance_seconds'))->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
            'grace_until' => $graceUntil->toISOString(),
        ];

        $lease = $this->verifier->sign($payload);
        $this->recordEvent($activation, 'lease_issued', [
            'lease_id' => $payload['lease_id'],
            'kid' => $lease['kid'],
            'lease_version' => $lease['lease_version'],
        ]);

        return $lease;
    }

    /**
     * @return array<string, mixed>
     */
    public function issueAndPersist(DeviceActivation $activation, ?EdgeConfiguration $configuration = null): array
    {
        $lease = $this->issue($activation);
        $verification = $this->verifier->persistVerified($lease, $configuration);

        if (! in_array($verification['state'], [LicenseLeaseVerifier::ACTIVE, LicenseLeaseVerifier::GRACE, LicenseLeaseVerifier::SUSPENDED, LicenseLeaseVerifier::REVOKED], true)) {
            throw new \RuntimeException('The issued commercial lease could not be verified locally.');
        }

        return $lease;
    }

    /**
     * @return array<string, mixed>
     */
    public function currentStatus(?EdgeConfiguration $configuration = null): array
    {
        $verification = $this->verifier->current($configuration);
        $claims = $verification['claims'] ?? [];

        return [
            'state' => $verification['state'],
            'usable' => $verification['usable'],
            'reason' => $verification['reason'],
            'license' => [
                'id' => $claims['license_id'] ?? null,
                'organization_id' => $claims['organization_id'] ?? null,
                'status' => $claims['license_status'] ?? null,
                'entitlements' => $claims['entitlements'] ?? [],
                'issued_at' => $claims['issued_at'] ?? null,
                'not_before' => $claims['not_before'] ?? null,
                'expires_at' => $claims['expires_at'] ?? null,
                'grace_until' => $claims['grace_until'] ?? null,
                'lease_version' => $claims['lease_version'] ?? null,
                'kid' => $verification['kid'] ?? null,
            ],
            'device' => [
                'id' => $claims['device_id'] ?? $configuration?->device_id,
                'installation_id' => $claims['installation_id'] ?? $configuration?->installation_id,
                'branch_id' => $claims['branch_id'] ?? $configuration?->branch_id,
                'device_type' => $claims['device_type'] ?? 'EDGE',
            ],
            'clock' => $verification['clock'] ?? ['status' => 'UNKNOWN'],
            'last_cloud_contact' => $this->lastCloudContact(),
        ];
    }

    /**
     * Refresh the local lease without making request handling depend on Cloud.
     *
     * @return array<string, mixed>
     */
    public function refresh(?EdgeConfiguration $configuration = null): array
    {
        $configuration ??= EdgeConfiguration::query()->where('installation_id', config('edge.installation_id'))->first();
        $endpoint = $configuration?->cloud_endpoint ?: config('edge.cloud_endpoint');
        if (! $configuration || blank($endpoint)) {
            return ['status' => 'NOT_CONFIGURED', ...$this->currentStatus($configuration)];
        }

        $activation = DeviceActivation::query()
            ->where('installation_id', $configuration->installation_id)
            ->whereNotNull('activation_credential')
            ->with(['license', 'device'])
            ->first();
        if (! $activation) {
            return ['status' => 'RECOVERY_REQUIRED', ...$this->currentStatus($configuration)];
        }

        $current = $this->verifier->current($configuration);
        $refreshState = $this->store->readRefreshState() ?? [];
        if (! empty($refreshState['next_attempt_at']) && CarbonImmutable::parse($refreshState['next_attempt_at'])->isFuture()) {
            return ['status' => 'BACKOFF', ...$this->currentStatus($configuration)];
        }

        if ($this->refreshIsNotDue($current)) {
            return ['status' => 'SKIPPED', ...$this->currentStatus($configuration)];
        }

        $attemptedAt = CarbonImmutable::now();
        $this->store->writeRefreshState([
            ...($this->store->readRefreshState() ?? []),
            'last_attempt_at' => $attemptedAt->toISOString(),
        ]);

        try {
            $response = Http::retry([100, 500], throw: false)
                ->timeout(5)
                ->connectTimeout(3)
                ->withHeaders(['X-Activation-Token' => $activation->activation_credential])
                ->post(rtrim($endpoint, '/').'/api/v1/activation/lease');
        } catch (ConnectionException $exception) {
            return $this->refreshFailure($activation, $configuration, $exception->getMessage());
        }

        if (! $response->successful()) {
            return $this->refreshFailure($activation, $configuration, 'Cloud returned HTTP '.$response->status());
        }

        $lease = $response->json('data.lease');
        if (! is_array($lease)) {
            return $this->refreshFailure($activation, $configuration, 'Cloud returned a malformed lease.');
        }

        $verification = $this->verifier->persistVerified($lease, $configuration);
        if ($verification['state'] === LicenseLeaseVerifier::INVALID || $verification['state'] === LicenseLeaseVerifier::TIME_VERIFICATION_REQUIRED) {
            $this->recordEvent($activation, 'lease_verification_failed', ['reason' => $verification['reason']]);

            return ['status' => 'INVALID_RESPONSE', ...$this->currentStatus($configuration)];
        }

        $this->store->writeRefreshState([
            'last_attempt_at' => $attemptedAt->toISOString(),
            'last_success_at' => CarbonImmutable::now()->toISOString(),
            'backoff_seconds' => 0,
        ]);
        $this->recordEvent($activation, 'lease_refreshed', ['lease_id' => $lease['payload']['lease_id'] ?? null]);

        return ['status' => 'REFRESHED', ...$this->currentStatus($configuration)];
    }

    private function refreshIsNotDue(array $verification): bool
    {
        if (! in_array($verification['state'] ?? null, [LicenseLeaseVerifier::ACTIVE], true)) {
            return false;
        }

        $expiresAt = $verification['claims']['expires_at'] ?? null;
        if (! $expiresAt) {
            return false;
        }

        return CarbonImmutable::parse($expiresAt)->greaterThan(
            CarbonImmutable::now()->addSeconds((int) config('commercial.license.refresh_window_seconds')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function refreshFailure(DeviceActivation $activation, EdgeConfiguration $configuration, string $message): array
    {
        $state = $this->store->readRefreshState() ?? [];
        $backoff = min(
            max((int) ($state['backoff_seconds'] ?? 0) * 2, (int) config('commercial.license.refresh_backoff_seconds')),
            86400,
        );
        $this->store->writeRefreshState([
            ...$state,
            'last_failure_at' => CarbonImmutable::now()->toISOString(),
            'backoff_seconds' => $backoff,
            'next_attempt_at' => CarbonImmutable::now()->addSeconds($backoff)->toISOString(),
            'last_error' => $message,
        ]);
        Log::notice('Commercial lease refresh failed; retaining the last verified lease.', ['message' => $message]);
        $this->recordEvent($activation, 'lease_refresh_failed', ['message' => $message]);

        return ['status' => 'CLOUD_UNAVAILABLE', ...$this->currentStatus($configuration)];
    }

    private function lastCloudContact(): ?string
    {
        $state = $this->store->readRefreshState() ?? [];

        return $state['last_success_at'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(DeviceActivation $activation, string $event, array $metadata = []): void
    {
        CommercialLicenseEvent::create([
            'license_id' => $activation->license_id,
            'device_activation_id' => $activation->getKey(),
            'event' => $event,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
