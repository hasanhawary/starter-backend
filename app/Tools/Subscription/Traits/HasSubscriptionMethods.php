<?php

namespace App\Tools\Subscription\Traits;

/**
 * Has Subscription Methods Trait
 *
 * Provides subscription-related methods for models.
 * Use this in Tenant or User models.
 */
trait HasSubscriptionMethods
{
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            $this->starts_at <= now() &&
            ($this->ends_at === null || $this->ends_at >= now());
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->ends_at !== null && $this->ends_at < now());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function hasFeature(string $featureKey): bool
    {
        return $this->plan->features()->where('key', $featureKey)->exists();
    }

    public function getFeatureValue(string $featureKey): ?string
    {
        return $this->plan->features()->where('key', $featureKey)->value('value');
    }
}
