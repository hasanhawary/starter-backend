<?php

namespace App\Tools\Subscription\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Subscription Usage Query Scopes
 *
 * Provides reusable query scopes for subscription usage queries.
 */
trait SubscriptionUsageScopes
{
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForFeature(Builder $query, string $featureKey): Builder
    {
        return $query->where('key', $featureKey);
    }

    public function scopeCurrentPeriod(Builder $query): Builder
    {
        return $query->where('period_start', '<=', now())
                     ->where('period_end', '>=', now());
    }

    public function scopeInPeriod(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('period_start', [$startDate, $endDate])
                     ->orWhereBetween('period_end', [$startDate, $endDate]);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->whereYear('period_start', $year)
                     ->whereMonth('period_start', $month);
    }

    public function scopeHighUsage(Builder $query, int $threshold): Builder
    {
        return $query->where('used_value', '>=', $threshold);
    }
}
