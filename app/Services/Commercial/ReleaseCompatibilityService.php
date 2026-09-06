<?php

namespace App\Services\Commercial;

use App\Enum\Commercial\ReleaseChannelEnum;
use App\Models\EdgeConfiguration;
use App\Models\Release;
use Illuminate\Support\Str;

class ReleaseCompatibilityService
{
    public function channelFor(EdgeConfiguration $configuration): string
    {
        $licenseChannel = $configuration->metadata['license_channel'] ?? null;
        $deviceChannel = $configuration->device?->metadata['update_channel'] ?? null;
        $branchChannel = $configuration->branch?->pos_settings['update_channel'] ?? null;

        return Str::upper((string) ($licenseChannel ?: $deviceChannel ?: $branchChannel ?: config('commercial.releases.default_channel', ReleaseChannelEnum::Stable->value)));
    }

    public function isEligible(Release $release, EdgeConfiguration $configuration, string $currentVersion): bool
    {
        if ($release->status !== 'PUBLISHED' || $release->channel !== $this->channelFor($configuration)) {
            return false;
        }

        if (version_compare($release->product_version, $currentVersion, '<=')) {
            return false;
        }

        if ($release->minimum_current_version && version_compare($currentVersion, $release->minimum_current_version, '<')) {
            return false;
        }

        if ($release->minimum_supported_version && version_compare($currentVersion, $release->minimum_supported_version, '<')) {
            return false;
        }

        $edgeMinimum = $release->compatibility['edge_minimum_version'] ?? null;
        $edgeMaximum = $release->compatibility['edge_maximum_version'] ?? null;
        if (($edgeMinimum && version_compare($currentVersion, $edgeMinimum, '<')) || ($edgeMaximum && version_compare($currentVersion, $edgeMaximum, '>'))) {
            return false;
        }

        $printAgentVersion = $configuration->metadata['print_agent_version'] ?? null;
        if ($release->print_agent_minimum_version && $printAgentVersion !== null && version_compare((string) $printAgentVersion, $release->print_agent_minimum_version, '<')) {
            return false;
        }

        if ($release->rollout && ! $this->isInRollout($release, $configuration)) {
            return false;
        }

        return true;
    }

    public function compatibilityFailure(Release $release, string $currentVersion): ?string
    {
        if ($release->minimum_current_version && version_compare($currentVersion, $release->minimum_current_version, '<')) {
            return 'INTERMEDIATE_UPDATE_REQUIRED';
        }

        if ($release->minimum_supported_version && version_compare($currentVersion, $release->minimum_supported_version, '<')) {
            return 'MINIMUM_SUPPORTED_VERSION_REQUIRED';
        }

        $edgeMinimum = $release->compatibility['edge_minimum_version'] ?? null;
        if ($edgeMinimum && version_compare($currentVersion, $edgeMinimum, '<')) {
            return 'INTERMEDIATE_UPDATE_REQUIRED';
        }

        if (version_compare($release->product_version, $currentVersion, '<')) {
            return 'DOWNGRADE_NOT_ALLOWED';
        }

        return null;
    }

    private function isInRollout(Release $release, EdgeConfiguration $configuration): bool
    {
        $rollout = $release->rollout ?? [];
        $installationId = $configuration->installation_id;
        $allowedInstallations = $rollout['installation_ids'] ?? [];
        if ($allowedInstallations !== [] && ! in_array($installationId, $allowedInstallations, true)) {
            return false;
        }

        $percentage = (int) ($rollout['percentage'] ?? 100);
        if ($percentage >= 100) {
            return true;
        }

        if ($percentage <= 0) {
            return false;
        }

        $bucket = hexdec(substr(hash('sha256', $release->release_id.'|'.$installationId), 0, 8)) % 100;

        return $bucket < $percentage;
    }
}
