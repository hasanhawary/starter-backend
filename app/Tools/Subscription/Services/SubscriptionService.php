<?php

namespace App\Tools\Subscription\Services;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\Central\SubscriptionUsage;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    public function changeStatus(Subscription $subscription, SubscriptionStatusEnum $status): Subscription
    {
        $subscription->update([
            'status' => $status->value,
        ]);

        return $subscription->refresh();
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatusEnum::Cancelled->value,
            'ends_at' => $subscription->ends_at ?? now(),
        ]);

        return $subscription->refresh();
    }

    public function renew(Subscription $subscription, Carbon $startsAt, ?Carbon $endsAt = null): Subscription
    {
        $subscription->update([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatusEnum::Active->value,
        ]);

        return $subscription->refresh();
    }

    public function createSubscription(array $data): Subscription
    {
        $plan = Plan::findOrFail($data['plan_id']);

        $startsAt = $data['starts_at'] ?? now();
        $endsAt = $data['ends_at']
            ?? $this->calculateEndDate($startsAt, $plan->billing_cycle);

        return Subscription::create([
            'tenant_id' => $data['tenant_id'],
            'plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatusEnum::Active->value,
        ]);
    }

    public function updateSubscription(Subscription $subscription, array $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {

            $subscription->fill([
                'tenant_id' => $data['tenant_id'] ?? $subscription->tenant_id,
                'plan_id' => $data['plan_id'] ?? $subscription->plan_id,
                'starts_at' => $data['starts_at'] ?? $subscription->starts_at,
                'ends_at' => $data['ends_at'] ?? $subscription->ends_at,
                'status' => isset($data['status'])
                    ? SubscriptionStatusEnum::from($data['status'])->value
                    : $subscription->status,
            ]);

            // Recalculate end date if plan or start changed and end not sent
            if (
                isset($data['plan_id'], $data['starts_at']) &&
                !isset($data['ends_at'])
            ) {
                $plan = Plan::findOrFail($data['plan_id']);
                $subscription->ends_at = $this->calculateEndDate($data['starts_at'], $plan->billing_cycle);
            }

            $subscription->save();

            return $subscription->refresh();
        });
    }


    public function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    public function getActiveSubscription(string $tenantId): ?Subscription
    {
        return Subscription::where('tenant_id', $tenantId)
            ->where('status', SubscriptionStatusEnum::Active->value)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with('plan')
            ->first();
    }

    public function expireIfNeeded(Subscription $subscription): bool
    {
        if (
            $subscription->ends_at &&
            $subscription->ends_at->isPast() &&
            $this->isActive($subscription)
        ) {
            $subscription->update([
                'status' => SubscriptionStatusEnum::Expired->value,
            ]);

            return true;
        }

        return false;
    }

    protected function resolveUsagePeriod(Subscription $subscription): array
    {
        $start = now()->startOfDay();

        $end = match ($subscription->plan->billing_cycle) {
            'weekly' => $start->copy()->endOfWeek(),
            'monthly' => $start->copy()->endOfMonth(),
            'quarterly' => $start->copy()->addMonths(3)->subSecond(),
            'yearly' => $start->copy()->endOfYear(),
            default => $start->copy()->endOfMonth(),
        };

        return [$start, $end];
    }

    public function trackUsage(string $tenantId, string $featureKey, int $amount = 1): SubscriptionUsage
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            throw new RuntimeException('No active subscription found.');
        }

        [$periodStart, $periodEnd] = $this->resolveUsagePeriod($subscription);

        return DB::transaction(function () use (
            $tenantId,
            $featureKey,
            $amount,
            $periodStart,
            $periodEnd
        ) {
            $usage = SubscriptionUsage::lockForUpdate()
                ->forTenant($tenantId)
                ->forFeature($featureKey)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->first();

            if ($usage) {
                $usage->increment('used_value', $amount);
                return $usage->refresh();
            }

            return SubscriptionUsage::create([
                'tenant_id' => $tenantId,
                'key' => $featureKey,
                'used_value' => $amount,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);
        });
    }

    public function canUseFeature(string $tenantId, string $featureKey, ?int $limit): bool
    {
        if ($limit === null || $limit < 0) {
            return true; // unlimited
        }

        $usage = SubscriptionUsage::forTenant($tenantId)
            ->forFeature($featureKey)
            ->currentPeriod()
            ->first();

        return !$usage || $usage->used_value < $limit;
    }

    public function consumeFeature(string $tenantId, string $featureKey, int $amount = 1): void
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            throw new RuntimeException('No active subscription.');
        }

        $limit = $subscription->getFeatureValue($featureKey);

        if (
            $limit !== null &&
            !$this->canUseFeature($tenantId, $featureKey, (int)$limit)
        ) {
            throw new DomainException('Feature limit exceeded.');
        }

        $this->trackUsage($tenantId, $featureKey, $amount);
    }

    public function getUsageStats(string $tenantId): array
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return [];
        }

        $usages = SubscriptionUsage::forTenant($tenantId)
            ->currentPeriod()
            ->get();

        $stats = [];

        foreach ($usages as $usage) {
            $limit = $subscription->getFeatureValue($usage->key);

            $stats[$usage->key] = [
                'used' => $usage->used_value,
                'limit' => $limit !== null ? (int)$limit : null,
                'percentage' => $limit
                    ? round(($usage->used_value / (int)$limit) * 100, 2)
                    : null,
            ];
        }

        return $stats;
    }

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
