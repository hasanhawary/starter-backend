<?php

namespace App\Services\Tenant;

use App\Models\Central\PlanPrice;
use App\Models\Central\Tenant;
use App\Tools\Subscription\Facades\Subscription;
use Illuminate\Support\Facades\DB;

class TenantService
{
    /**
     * Create a tenant (with optional subscription)
     *
     * @param array $data Tenant data ['name' => ..., 'domain' => ..., ...]
     * @param int|null $planPriceId Optional plan price id to create subscription
     * @param string|null $subscriptionStartsAt Optional subscription start date
     * @return Tenant
     * @throws \Throwable
     */
    public function createTenant(array $data, ?int $planPriceId = null, ?string $subscriptionStartsAt = null): Tenant
    {
        return DB::transaction(function () use ($data, $planPriceId, $subscriptionStartsAt) {

            $tenant = Tenant::create($data);

            if ($planPriceId) {
                $this->createSubscriptionForTenant($tenant, $planPriceId, $subscriptionStartsAt);
            }

            return $tenant->refresh();
        });
    }

    /**
     * Update tenant (and optionally its subscription)
     *
     * @param Tenant $tenant
     * @param array $data Tenant fields to update
     * @param int|null $planPriceId Optional new plan price id to update subscription
     * @param string|null $subscriptionStartsAt Optional subscription start date
     * @return Tenant
     * @throws \Throwable
     */
    public function updateTenant(Tenant $tenant, array $data, ?int $planPriceId = null, ?string $subscriptionStartsAt = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $planPriceId, $subscriptionStartsAt) {

            $tenant->update($data);

            if ($planPriceId) {
                $subscription = Subscription::getActiveSubscription($tenant->id);

                if ($subscription) {
                    Subscription::updateSubscription($subscription, [
                        'plan_price_id' => $planPriceId,
                    ]);
                } else {
                    $this->createSubscriptionForTenant($tenant, $planPriceId, $subscriptionStartsAt);
                }
            }

            return $tenant->refresh();
        });
    }

    /**
     * Create subscription for tenant
     *
     * @param Tenant $tenant
     * @param int $planPriceId
     * @param string|null $startsAt
     * @return void
     */
    protected function createSubscriptionForTenant(Tenant $tenant, int $planPriceId, ?string $startsAt = null): void
    {
        try {
            // Get plan price to retrieve plan_id
            $planPrice = PlanPrice::findOrFail($planPriceId);

            $subscriptionData = [
                'tenant_id' => $tenant->id,
                'plan_id' => $planPrice->plan_id,
                'plan_price_id' => $planPriceId,
            ];

            if ($startsAt) {
                $subscriptionData['starts_at'] = $startsAt;
            }

            Subscription::createSubscription($subscriptionData);

            \Log::info('Subscription created for tenant', [
                'tenant_id' => $tenant->id,
                'plan_id' => $planPrice->plan_id,
                'plan_price_id' => $planPriceId,
            ]);
        } catch (\Throwable $exception) {
            \Log::warning('Failed to create subscription for tenant', [
                'tenant_id' => $tenant->id,
                'plan_price_id' => $planPriceId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

