<?php

namespace App\Tools\Subscription\Facades;

use App\Models\Central\SubscriptionUsage;
use Illuminate\Support\Facades\Facade;

/**
 * Subscription Facade
 *
 * Provides a convenient static interface to subscription services.
 *
 * ========================
 * Plans
 * ========================
 * @method static \Illuminate\Support\Collection getActivePlans()
 * @method static \App\Models\Central\Plan|null getPlan(int $planId)
 * @method static \App\Models\Central\Plan createPlan(array $data)
 * @method static \App\Models\Central\Plan createFullPlan(array $planData, array $features = [], array $prices = [])
 * @method static \App\Models\Central\Plan updatePlan(\App\Models\Central\Plan $plan, array $data)
 * @method static \App\Models\Central\Plan updateFullPlan(\App\Models\Central\Plan $plan, array $planData, array $features = [], array $prices = [])
 *
 * ========================
 * Subscriptions
 * ========================
 * @method static \App\Models\Central\Subscription createSubscription(array $data)
 * @method static \App\Models\Central\Subscription updateSubscription(\App\Models\Central\Subscription $subscription, array $data)
 * @method static \App\Models\Central\Subscription changeStatus(\App\Models\Central\Subscription $subscription, \App\Enum\Subscription\SubscriptionStatusEnum $status)
 * @method static \App\Models\Central\Subscription cancel(\App\Models\Central\Subscription $subscription)
 * @method static \App\Models\Central\Subscription renew(\App\Models\Central\Subscription $subscription, \Carbon\Carbon $startsAt, ?\Carbon\Carbon $endsAt = null)
 * @method static \App\Models\Central\Subscription|null getActiveSubscription(string $tenantId)
 * @method static void expireIfNeeded(\App\Models\Central\Subscription $subscription)
 * @method static SubscriptionUsage trackUsage(string $tenantId, string $featureKey, int $amount = 1)
 * @method static bool canUseFeature(string $tenantId, string $featureKey, ?int $limit)
 * @method static void consumeFeature(string $tenantId, string $featureKey, int $amount = 1)
 * @method static array getUsageStats(string $tenantId)
 * @method static array resolveUsagePeriod(Subscription $subscription)
 * @method static bool hasStatus(Subscription $subscription, $status)
 * @method static bool isActive(Subscription $subscription)
 * @method static bool isExpired(Subscription $subscription)
 * @method static bool isCancelled(Subscription $subscription)
 * /
 */
class Subscription extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'subscription';
    }
}
