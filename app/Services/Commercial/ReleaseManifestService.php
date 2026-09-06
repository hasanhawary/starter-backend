<?php

namespace App\Services\Commercial;

use App\Models\Release;

class ReleaseManifestService
{
    /**
     * @return array<string, mixed>
     */
    public function manifest(Release $release): array
    {
        return [
            'manifest_version' => (int) config('commercial.releases.manifest_version'),
            'release_id' => $release->release_id,
            'version' => $release->product_version,
            'channel' => $release->channel,
            'published_at' => $release->published_at?->toISOString(),
            'package' => [
                'reference' => $release->package_reference,
                'size' => $release->package_size,
                'sha256' => $release->package_sha256,
            ],
            'components' => [
                'desktop' => $release->desktop_version,
                'edge' => $release->edge_version,
                'print_agent_minimum' => $release->print_agent_minimum_version,
                'print_agent_recommended' => $release->print_agent_recommended_version,
            ],
            'schema' => [
                'version' => $release->schema_version,
                'minimum' => $release->schema_minimum_version,
                'maximum' => $release->schema_maximum_version,
            ],
            'compatibility' => [
                ...($release->compatibility ?? []),
                'minimum_current_version' => $release->minimum_current_version,
                'minimum_supported_version' => $release->minimum_supported_version,
            ],
            'mandatory' => $release->mandatory,
            'mandatory_deadline' => $release->mandatory_deadline?->toISOString(),
            'rollout' => $release->rollout,
            'release_notes' => $release->release_notes,
        ];
    }

    /**
     * @return array{manifest_version: int, kid: string, payload: array<string, mixed>, signature: string}
     */
    public function sign(Release $release): array
    {
        $keyId = (string) config('commercial.releases.signing_key_id');
        $privateKey = $this->decodeKey(config('commercial.releases.signing_private_key'), SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
        $payload = $this->manifest($release);

        if ($privateKey === null) {
            throw new \RuntimeException('Commercial release signing is not configured.');
        }

        $message = $this->canonicalJson(['manifest_version' => $payload['manifest_version'], 'kid' => $keyId, 'payload' => $payload]);

        return [
            'manifest_version' => $payload['manifest_version'],
            'kid' => $keyId,
            'payload' => $payload,
            'signature' => $this->base64UrlEncode(sodium_crypto_sign_detached($message, $privateKey)),
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{valid: bool, reason: string|null, payload: array<string, mixed>}
     */
    public function verify(array $manifest): array
    {
        $version = $manifest['manifest_version'] ?? null;
        $keyId = $manifest['kid'] ?? null;
        $payload = $manifest['payload'] ?? null;
        $signature = $manifest['signature'] ?? null;

        if (! is_array($payload) || ! is_numeric($version) || blank($keyId) || blank($signature)) {
            return $this->invalid('MALFORMED_MANIFEST');
        }

        $version = (int) $version;
        if ($version !== (int) config('commercial.releases.manifest_version') || (int) ($payload['manifest_version'] ?? -1) !== $version) {
            return $this->invalid('UNSUPPORTED_MANIFEST_VERSION');
        }

        $publicKey = $this->decodeKey(config('commercial.releases.trusted_public_keys.'.(string) $keyId), SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $decodedSignature = $this->base64UrlDecode((string) $signature);
        if ($publicKey === null || $decodedSignature === null || ! sodium_crypto_sign_verify_detached(
            $decodedSignature,
            $this->canonicalJson(['manifest_version' => $version, 'kid' => (string) $keyId, 'payload' => $payload]),
            $publicKey,
        )) {
            return $this->invalid(blank(config('commercial.releases.trusted_public_keys.'.(string) $keyId)) ? 'UNKNOWN_SIGNING_KEY' : 'INVALID_SIGNATURE');
        }

        foreach (['release_id', 'version', 'channel', 'package', 'components', 'schema', 'compatibility'] as $claim) {
            if (! array_key_exists($claim, $payload)) {
                return $this->invalid('MISSING_MANIFEST_CLAIM');
            }
        }

        if (! is_array($payload['package']) || blank($payload['package']['sha256'] ?? null)) {
            return $this->invalid('MISSING_PACKAGE_CHECKSUM');
        }

        return ['valid' => true, 'reason' => null, 'payload' => $payload];
    }

    public function canonicalJson(array $value): string
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

    /**
     * @return array{valid: false, reason: string, payload: array<string, mixed>}
     */
    private function invalid(string $reason): array
    {
        return ['valid' => false, 'reason' => $reason, 'payload' => []];
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

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
