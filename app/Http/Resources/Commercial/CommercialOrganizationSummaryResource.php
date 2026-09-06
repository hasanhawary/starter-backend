<?php

namespace App\Http\Resources\Commercial;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommercialOrganizationSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Organization $org */
        $org = $this->resource;

        $activeLicense = $org->relationLoaded('licenses')
            ? $org->licenses->firstWhere('status', 'active') ?? $org->licenses->first()
            : $org->licenses()->latest()->first();

        $activeDevicesCount = $org->relationLoaded('branches')
            ? $org->branches->flatMap->devices->where('is_active', true)->count()
            : ($org->devices_count ?? $org->branches()->withCount(['devices' => fn ($q) => $q->where('is_active', true)])->get()->sum('devices_count'));

        $activeBranchesCount = $org->relationLoaded('branches')
            ? $org->branches->where('is_active', true)->count()
            : ($org->branches_count ?? $org->branches()->where('is_active', true)->count());

        $latestDevice = $org->relationLoaded('branches')
            ? $org->branches->flatMap->devices->sortByDesc('last_seen_at')->first()
            : null;

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

        // Derive operational state
        $hasActivations = $activeLicense && ($org->relationLoaded('licenses')
            ? $activeLicense->activations->where('status', 'active')->isNotEmpty()
            : $activeLicense->activations()->where('status', 'active')->exists());

        $derivedStatus = 'PROVISIONED';
        if ($org->onboarding_status === 'COMPLETED') {
            $derivedStatus = $heartbeatStatus === 'ACTIVE' ? 'OPERATIONAL' : 'SETUP_COMPLETED';
        } elseif ($org->onboarding_status === 'IN_PROGRESS') {
            $derivedStatus = 'SETUP_IN_PROGRESS';
        } elseif ($hasActivations) {
            $derivedStatus = 'ACTIVATED';
        }

        $licenseData = null;
        if ($activeLicense) {
            $now = CarbonImmutable::now();
            $expiresAt = $activeLicense->expires_at ? CarbonImmutable::parse($activeLicense->expires_at) : null;
            $daysRemaining = $expiresAt ? (int) $now->diffInDays($expiresAt, false) : null;

            $licenseData = [
                'id' => $activeLicense->getKey(),
                'status' => $activeLicense->status,
                'plan_code' => $activeLicense->plan_code ?: $activeLicense->plan,
                'plan_name' => $activeLicense->commercialPlan?->name,
                'key_last_four' => $activeLicense->key_last_four,
                'starts_at' => $activeLicense->starts_at?->toISOString(),
                'expires_at' => $activeLicense->expires_at?->toISOString(),
                'grace_period_days' => $activeLicense->grace_period_days,
                'max_devices' => $activeLicense->max_devices,
                'max_branches' => $activeLicense->max_branches,
                'is_over_limit_devices' => $activeDevicesCount > $activeLicense->max_devices,
                'is_over_limit_branches' => $activeBranchesCount > $activeLicense->max_branches,
                'days_remaining' => $daysRemaining,
                'is_expired' => $expiresAt ? $expiresAt->isPast() : false,
            ];
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
            'onboarding_completed_at' => $org->onboarding_completed_at?->toISOString(),
            'is_active' => (bool) $org->is_active,
            'license' => $licenseData,
            'active_devices_count' => $activeDevicesCount,
            'active_branches_count' => $activeBranchesCount,
            'last_seen_at' => $lastSeenAt?->toISOString(),
            'heartbeat_status' => $heartbeatStatus,
            'derived_status' => $derivedStatus,
            'created_at' => $org->created_at?->toISOString(),
            'updated_at' => $org->updated_at?->toISOString(),
        ];
    }
}
