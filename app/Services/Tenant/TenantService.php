<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Tools\Subscription\Facades\Subscription;
use Illuminate\Support\Facades\DB;

class TenantService
{
    /**
     * Create a tenant (with optional subscription)
     *
     * @param array $data Tenant data ['name' => ..., 'domain' => ..., ...]
     * @param int|null $planId Optional plan id to create subscription
     * @return Tenant
     * @throws \Throwable
     */
    public function createTenant(array $data, ?int $planId = null): Tenant
    {
        return DB::transaction(function () use ($data, $planId) {

            $tenant = Tenant::create($data);

            if ($planId) {
                Subscription::createSubscription([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $planId,
                ]);
            }

            return $tenant->refresh();
        });
    }

    /**
     * Update tenant (and optionally its subscription)
     *
     * @param Tenant $tenant
     * @param array $data Tenant fields to update
     * @param int|null $planId Optional new plan id to update subscription
     * @return Tenant
     * @throws \Throwable
     */
    public function updateTenant(Tenant $tenant, array $data, ?int $planId = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $planId) {

            $tenant->update($data);

            if ($planId) {
                $subscription = Subscription::getActiveSubscription($tenant->id);

                if ($subscription) {
                    Subscription::updateSubscription($subscription, [
                        'plan_id' => $planId,
                    ]);
                } else {
                    Subscription::createSubscription([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $planId,
                    ]);
                }
            }

            return $tenant->refresh();
        });
    }
}
