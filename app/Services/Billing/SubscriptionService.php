<?php

namespace App\Services\Billing;

use App\Enum\Billing\SubscriptionStatusEnum;
use App\Models\Central\Subscription;

class SubscriptionService
{
    /**
     * Change subscription status using enum.
     *
     * @param Subscription $subscription
     * @param SubscriptionStatusEnum $status
     * @return Subscription
     */
    public function changeStatus(Subscription $subscription, SubscriptionStatusEnum $status): Subscription
    {
        $subscription->status = $status->value;
        $subscription->save();

        return $subscription->refresh();
    }

    /**
     * Cancel a subscription: set status to Cancelled and, if missing, set ends_at to now.
     *
     * @param Subscription $subscription
     * @return Subscription
     */
    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->status = SubscriptionStatusEnum::Cancelled->value;

        if (! $subscription->ends_at) {
            $subscription->ends_at = now();
        }

        $subscription->save();

        return $subscription->refresh();
    }

    /**
     * Renew a subscription with new period dates and set status to Active.
     *
     * @param Subscription $subscription
     * @param string $startsAt
     * @param string|null $endsAt
     * @return Subscription
     */
    public function renew(Subscription $subscription, string $startsAt, ?string $endsAt): Subscription
    {
        $subscription->starts_at = $startsAt;
        $subscription->ends_at = $endsAt;
        $subscription->status = SubscriptionStatusEnum::Active->value;
        $subscription->save();

        return $subscription->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers (usable from controllers, middleware, services)
    |--------------------------------------------------------------------------
    */

    public function hasStatus(Subscription $subscription, SubscriptionStatusEnum $status): bool
    {
        return $subscription->status === $status->value;
    }

    public function isActive(Subscription $subscription): bool
    {
        return $this->hasStatus($subscription, SubscriptionStatusEnum::Active);
    }

    public function isExpired(Subscription $subscription): bool
    {
        return $this->hasStatus($subscription, SubscriptionStatusEnum::Expired);
    }

    public function isCancelled(Subscription $subscription): bool
    {
        return $this->hasStatus($subscription, SubscriptionStatusEnum::Cancelled);
    }
}
