<?php

namespace App\Tools\Subscription\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Plan Query Scopes
 *
 * Provides reusable query scopes for plan queries.
 */
trait PlanScopes
{
    /**
     * Scope to filter active plans
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive plans
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter plans by currency
     */
    public function scopeForCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope to filter plans by billing cycle
     */
    public function scopeForBillingCycle(Builder $query, string $cycle): Builder
    {
        return $query->where('billing_cycle', $cycle);
    }

    /**
     * Scope to filter plans with features
     */
    public function scopeWithFeatures(Builder $query): Builder
    {
        return $query->with('features');
    }

    /**
     * Scope to filter plans with prices
     */
    public function scopeWithPrices(Builder $query): Builder
    {
        return $query->with('prices');
    }

    /**
     * Scope to filter plans by code
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to search plans by name or code
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

}
