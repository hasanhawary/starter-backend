<?php

namespace App\Services\Commercial;

use App\Models\EdgeConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseLeaseVerifier
{
    public const ACTIVE = 'ACTIVE';

    public const GRACE = 'GRACE';

    public const RESTRICTED = 'RESTRICTED';

    public const SUSPENDED = 'SUSPENDED';

    public const REVOKED = 'REVOKED';

    public const INVALID = 'INVALID';

    public const TIME_VERIFICATION_REQUIRED = 'TIME_VERIFICATION_REQUIRED';

    public function __construct(private readonly LicenseLeaseStore $store) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{lease_version: int, kid: string, payload: array<string, mixed>, signature: string}
     */
    public function sign(array $payload): array
    {
        $version = (int) config('commercial.license.lease_version');
        $keyId = (string) config('commercial.license.signing_key_id');
        $privateKey = $this->decodeKey(config('commercial.license.signing_private_key'), SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);

        if ($privateKey === null) {
            throw new \RuntimeException('Commercial lease signing is not configured.');
        }

        $payload['lease_version'] = $version;
        $message = $this->canonicalJson(['lease_version' => $version, 'kid' => $keyId, 'payload' => $payload]);

        return [
            'lease_version' => $version,
            'kid' => $keyId,
            'payload' => $payload,
            'signature' => $this->base64UrlEncode(sodium_crypto_sign_detached($message, $privateKey)),
        ];
    }

    /**
     * @param  array<string, mixed>  $lease
     * @return array<string, mixed>
     */
    public function verify(array $lease, ?EdgeConfiguration $configuration = null, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $payload = $lease['payload'] ?? null;
        $version = $lease['lease_version'] ?? null;
        $keyId = $lease['kid'] ?? null;
        $signature = $lease['signature'] ?? null;

        if (! is_array($payload) || ! is_int($version) && ! is_numeric($version) || blank($keyId) || blank($signature)) {
            return $this->invalid('MALFORMED_LEASE');
        }

        $version = (int) $version;
        if ($version !== (int) config('commercial.license.lease_version') || (int) ($payload['lease_version'] ?? -1) !== $version) {
            return $this->invalid('UNSUPPORTED_LEASE_VERSION');
        }

        $publicKeyValue = config('commercial.license.trusted_public_keys.'.(string) $keyId);
        if (! is_string($publicKeyValue) || blank($publicKeyValue)) {
            return $this->invalid('UNKNOWN_SIGNING_KEY');
        }

        $publicKey = $this->publicKey((string) $keyId);
        $decodedSignature = $this->base64UrlDecode((string) $signature);
        if ($publicKey === null
            || $decodedSignature === null
            || strlen($decodedSignature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached(
                $decodedSignature,
                $this->canonicalJson(['lease_version' => $version, 'kid' => (string) $keyId, 'payload' => $payload]),
                $publicKey,
            )) {
            return $this->invalid('INVALID_SIGNATURE');
        }

        $required = ['lease_id', 'license_id', 'organization_id', 'branch_id', 'device_id', 'installation_id', 'device_type', 'license_status', 'entitlements', 'issued_at', 'not_before', 'expires_at', 'grace_until'];
        foreach ($required as $claim) {
            if (! array_key_exists($claim, $payload)) {
                return $this->invalid('MISSING_CLAIM');
            }
        }

        try {
            $issuedAt = CarbonImmutable::parse((string) $payload['issued_at']);
            $notBefore = CarbonImmutable::parse((string) $payload['not_before']);
            $expiresAt = CarbonImmutable::parse((string) $payload['expires_at']);
            $graceUntil = CarbonImmutable::parse((string) $payload['grace_until']);
        } catch (\Throwable) {
            return $this->invalid('INVALID_CLAIM_TIME');
        }

        if ($expiresAt->lessThanOrEqualTo($notBefore) || $graceUntil->lessThan($expiresAt) || $issuedAt->lessThan($notBefore)) {
            return $this->invalid('INVALID_CLAIM_RANGE');
        }

        $bindingFailure = $this->bindingFailure($payload, $configuration);
        if ($bindingFailure !== null) {
            return $this->invalid($bindingFailure);
        }

        $clock = $this->clockStatus($now);
        if ($clock['status'] === 'ROLLBACK_DETECTED') {
            $this->recordSecurityEvent('clock_anomaly', ['status' => $clock['status']]);

            return [
                ...$this->invalid($clock['status']),
                'state' => self::TIME_VERIFICATION_REQUIRED,
                'clock' => $clock,
            ];
        }

        $licenseStatus = Str::upper((string) $payload['license_status']);
        $state = match ($licenseStatus) {
            'REVOKED' => self::REVOKED,
            'SUSPENDED' => self::SUSPENDED,
            default => $now->lessThan($notBefore->subSeconds((int) config('commercial.license.clock_tolerance_seconds')))
                ? self::INVALID
                : ($now->lessThanOrEqualTo($expiresAt)
                    ? self::ACTIVE
                    : ($now->lessThanOrEqualTo($graceUntil) ? self::GRACE : self::RESTRICTED)),
        };

        $result = [
            'state' => $state,
            'usable' => in_array($state, [self::ACTIVE, self::GRACE], true),
            'reason' => $state === self::GRACE ? 'LEASE_EXPIRED_WITHIN_GRACE' : null,
            'claims' => [
                ...$payload,
                'issued_at' => $issuedAt->toISOString(),
                'not_before' => $notBefore->toISOString(),
                'expires_at' => $expiresAt->toISOString(),
                'grace_until' => $graceUntil->toISOString(),
            ],
            'kid' => (string) $keyId,
            'clock' => $clock,
        ];

        if (in_array($state, [self::ACTIVE, self::GRACE, self::SUSPENDED, self::REVOKED], true)) {
            $this->recordTrustedTime($now, $issuedAt);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function current(?EdgeConfiguration $configuration = null, ?CarbonImmutable $now = null): array
    {
        $lease = $this->store->readLease();
        if (! $lease) {
            return $this->invalid($this->store->leaseFileExists() ? 'CORRUPT_LEASE' : 'MISSING_LEASE');
        }

        return $this->verify($lease, $configuration, $now);
    }

    /**
     * @param  array<string, mixed>  $lease
     * @return array<string, mixed>
     */
    public function persistVerified(array $lease, ?EdgeConfiguration $configuration = null): array
    {
        $verification = $this->verify($lease, $configuration);
        if ($verification['state'] === self::INVALID || $verification['state'] === self::TIME_VERIFICATION_REQUIRED) {
            $this->recordSecurityEvent('lease_rejected', ['reason' => $verification['reason']]);

            return $verification;
        }

        $current = $this->store->readLease();
        $currentIssuedAt = $current['payload']['issued_at'] ?? null;
        $incomingIssuedAt = $lease['payload']['issued_at'] ?? null;
        if ($currentIssuedAt && $incomingIssuedAt) {
            try {
                if (CarbonImmutable::parse((string) $incomingIssuedAt)->lessThan(CarbonImmutable::parse((string) $currentIssuedAt))) {
                    return $this->invalid('STALE_LEASE');
                }
            } catch (\Throwable) {
                return $this->invalid('INVALID_CLAIM_TIME');
            }
        }

        $this->store->writeLease($lease);

        return $verification;
    }

    /**
     * @return array<string, mixed>
     */
    private function publicKey(string $keyId): ?string
    {
        $encoded = config('commercial.license.trusted_public_keys.'.$keyId);

        return $this->decodeKey(is_string($encoded) ? $encoded : null, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
    }

    private function decodeKey(mixed $value, int $expectedLength): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        if (str_starts_with($value, 'base64:')) {
            $value = substr($value, 7);
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return null;
        }

        if ($expectedLength === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES && strlen($decoded) === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            $decoded = sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($decoded));
        }

        return strlen($decoded) === $expectedLength ? $decoded : null;
    }

    private function canonicalJson(array $value): string
    {
        $normalize = function (mixed $item) use (&$normalize): mixed {
            if (! is_array($item)) {
                return $item;
            }

            if (array_is_list($item)) {
                return array_map($normalize, $item);
            }

            $normalized = [];
            foreach ($item as $key => $nested) {
                $normalized[(string) $key] = $normalize($nested);
            }
            ksort($normalized, SORT_STRING);

            return $normalized;
        };

        return json_encode($normalize($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bindingFailure(array $payload, ?EdgeConfiguration $configuration): ?string
    {
        if (! $configuration) {
            return null;
        }

        $bindings = [
            'organization_id' => $configuration->organization_id,
            'branch_id' => $configuration->branch_id,
            'device_id' => $configuration->device_id,
            'installation_id' => $configuration->installation_id,
        ];
        foreach ($bindings as $claim => $expected) {
            if ($expected !== null && (string) ($payload[$claim] ?? '') !== (string) $expected) {
                return Str::upper($claim).'_MISMATCH';
            }
        }

        $deviceType = Str::upper((string) ($configuration->device?->type ?? 'edge'));
        if ($configuration->device && ! $configuration->device->is_active) {
            return 'DEVICE_REVOKED';
        }
        if (Str::upper((string) $payload['device_type']) !== $deviceType) {
            return 'DEVICE_TYPE_MISMATCH';
        }

        return null;
    }

    /**
     * @return array{status: string, last_trusted_local_time?: string, last_cloud_time?: string}
     */
    private function clockStatus(CarbonImmutable $now): array
    {
        $trusted = $this->store->readTrustedTime() ?? [];
        try {
            $lastLocal = isset($trusted['last_verified_local_time']) ? CarbonImmutable::parse($trusted['last_verified_local_time']) : null;
        } catch (\Throwable) {
            $lastLocal = null;
        }
        $tolerance = (int) config('commercial.license.clock_tolerance_seconds');

        if ($lastLocal && $now->lessThan($lastLocal->subSeconds($tolerance))) {
            return [
                'status' => 'ROLLBACK_DETECTED',
                'last_trusted_local_time' => $lastLocal->toISOString(),
                'last_cloud_time' => $trusted['last_cloud_time'] ?? null,
            ];
        }

        if ($lastLocal && $now->greaterThan($lastLocal->addSeconds((int) config('commercial.license.max_forward_jump_seconds')))) {
            return [
                'status' => 'FORWARD_JUMP_DETECTED',
                'last_trusted_local_time' => $lastLocal->toISOString(),
                'last_cloud_time' => $trusted['last_cloud_time'] ?? null,
            ];
        }

        return ['status' => 'OK'];
    }

    private function recordTrustedTime(CarbonImmutable $now, CarbonImmutable $cloudTime): void
    {
        $trusted = $this->store->readTrustedTime() ?? [];
        $trusted['last_verified_local_time'] = $now->toISOString();
        $trusted['last_successful_validation_at'] = $now->toISOString();
        $trusted['last_lease_issued_at'] = $cloudTime->toISOString();
        $trusted['last_cloud_time'] = max(
            (string) ($trusted['last_cloud_time'] ?? ''),
            $cloudTime->toISOString(),
        );
        $this->store->writeTrustedTime($trusted);
    }

    /**
     * @return array{state: string, usable: false, reason: string, claims: array<string, mixed>, clock: array<string, mixed>}
     */
    private function invalid(string $reason): array
    {
        return [
            'state' => self::INVALID,
            'usable' => false,
            'reason' => $reason,
            'claims' => [],
            'clock' => ['status' => 'UNKNOWN'],
        ];
    }

    /**
     * The event is intentionally log-only here. Lease audit rows are written by
     * the issuing/refreshing services where the related activation is known.
     *
     * @param  array<string, mixed>  $context
     */
    private function recordSecurityEvent(string $event, array $context): void
    {
        Log::warning('Commercial license security event.', ['event' => $event, ...$context]);
    }
}
