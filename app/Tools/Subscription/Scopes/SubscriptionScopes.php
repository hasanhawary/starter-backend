<?php

namespace App\Tools\Subscription\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait SubscriptionScopes
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->where('plan_id', $planId);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }
}
