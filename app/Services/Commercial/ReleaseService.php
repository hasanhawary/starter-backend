<?php

namespace App\Services\Commercial;

use App\Models\CommercialReleaseEvent;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\File;

class ReleaseService
{
    public function __construct(private readonly ReleaseManifestService $manifestService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Release
    {
        $packageReference = (string) $data['package_reference'];
        $packageSize = $data['package_size'] ?? null;
        $checksum = $data['package_sha256'] ?? null;
        if (is_string($packageReference) && File::isFile($packageReference)) {
            $packageSize ??= File::size($packageReference);
            $checksum ??= hash_file('sha256', $packageReference);
        }

        if (blank($checksum)) {
            throw new \InvalidArgumentException('A package SHA-256 checksum is required when the artifact is not local.');
        }

        $release = Release::create([
            ...$data,
            'package_size' => $packageSize,
            'package_sha256' => strtolower((string) $checksum),
            'status' => 'DRAFT',
            'created_by' => $actor?->getKey(),
        ]);
        $this->record($release, 'release_created', $actor, ['channel' => $release->channel]);

        return $release;
    }

    public function publish(Release $release, ?User $actor = null): Release
    {
        if ($release->status === 'REVOKED') {
            throw new \RuntimeException('A revoked release cannot be published.');
        }

        $this->assertArtifact($release);
        $release->forceFill([
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'published_by' => $actor?->getKey(),
        ])->save();

        $signedManifest = $this->manifestService->sign($release->fresh());
        $release->update([
            'signature' => $signedManifest['signature'],
            'signing_key_id' => $signedManifest['kid'],
        ]);
        $this->record($release, 'release_published', $actor, ['version' => $release->product_version]);

        return $release->fresh();
    }

    public function pause(Release $release, ?User $actor = null): Release
    {
        if ($release->status === 'REVOKED') {
            throw new \RuntimeException('A revoked release cannot be paused.');
        }

        $release->update(['status' => 'PAUSED']);
        $this->record($release, 'release_paused', $actor);

        return $release->fresh();
    }

    public function revoke(Release $release, ?User $actor = null): Release
    {
        $release->update(['status' => 'REVOKED']);
        $this->record($release, 'release_revoked', $actor);

        return $release->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function clientManifest(Release $release): array
    {
        $payload = $this->manifestService->manifest($release);

        return [
            'manifest_version' => $payload['manifest_version'],
            'kid' => $release->signing_key_id,
            'payload' => $payload,
            'signature' => $release->signature,
        ];
    }

    private function assertArtifact(Release $release): void
    {
        if (blank($release->signature) && blank(config('commercial.releases.signing_private_key'))) {
            throw new \RuntimeException('Release signing is not configured.');
        }

        if (File::isFile($release->package_reference)) {
            $actualChecksum = hash_file('sha256', $release->package_reference);
            if (! hash_equals(strtolower($release->package_sha256), strtolower((string) $actualChecksum))) {
                throw new \RuntimeException('The registered package checksum does not match the artifact.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(Release $release, string $event, ?User $actor, array $metadata = []): void
    {
        CommercialReleaseEvent::create([
            'release_id' => $release->getKey(),
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
