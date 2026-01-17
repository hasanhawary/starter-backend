<?php

namespace App\Tools\Subscription\Traits;

use App\Models\Central\Subscription;
use App\Tools\Subscription\Services\SubscriptionService;

/**
 * Has Subscription Methods Trait
 *
 * Provides subscription-related methods for models.
 * Use this in Tenant or User models.
 */
trait HasSubscriptionUsageMethods
{
    public function incrementUsage(int $amount = 1): bool
    {
        return $this->increment('used_value', $amount);
    }

    public function decrementUsage(int $amount = 1): bool
    {
        return $this->decrement('used_value', max(0, $amount));
    }

    public function resetUsage(): bool
    {
        return $this->update(['used_value' => 0]);
    }
}
