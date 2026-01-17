<?php

namespace App\Tools\Subscription\Traits;

use Illuminate\Support\Carbon;

/**
 * Has Subscription Methods Trait
 *
 * Provides subscription-related helper methods.
 * Intended for Subscription model.
 */
trait HasSubscriptionMethods
{
    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at <= now()
            && ($this->ends_at === null || $this->ends_at >= now());
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->ends_at !== null && $this->ends_at < now());
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get remaining days
     */
    public function daysRemaining(): ?int
    {
        if (!$this->ends_at instanceof Carbon) {
            return null;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Check if plan has feature
     */
    public function hasFeature(string $featureKey): bool
    {
        return $this->plan?->features()
            ->where('feature_key', $featureKey)
            ->exists() ?? false;
    }

    /**
     * Get feature value
     */
    public function getFeatureValue(string $featureKey): ?string
    {
        return $this->plan?->features()
            ->where('feature_key', $featureKey)
            ->value('value');
    }

    /**
     * Billing cycle (monthly / yearly)
     */
    public function getBillingCycle(): ?string
    {
        return $this->planPrice?->cycle?->value;
    }

    /**
     * Base price
     */
    public function getPrice(): ?float
    {
        return $this->planPrice?->price;
    }

    /**
     * Currency code
     */
    public function getCurrency(): ?string
    {
        return $this->planPrice?->currency;
    }

    /**
     * Discount percent
     */
    public function getDiscountPercent(): ?float
    {
        return $this->planPrice?->discount_percent;
    }

    /**
     * Discounted price
     */
    public function getDiscountedPrice(): ?float
    {
        return $this->planPrice?->getDiscountedPrice();
    }

    /**
     * Formatted price
     */
    public function getFormattedPrice(): ?string
    {
        return $this->planPrice?->getFormattedPrice();
    }

    /**
     * Formatted discounted price
     */
    public function getFormattedDiscountedPrice(): ?string
    {
        return $this->planPrice?->getFormattedDiscountedPrice();
    }
}
