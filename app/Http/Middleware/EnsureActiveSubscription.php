<?php

namespace App\Http\Middleware;

use App\Tools\Subscription\Facades\Subscription;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = Tenant::current();

        if (!$tenant) {
            return failResponse(trans('api.tenant_not_found'));
        }

        $tenantModel = \App\Models\Central\Tenant::where('domain', $tenant->domain)->first();

        Tenant::forgetCurrent();
        $subscription = Subscription::getActiveSubscription($tenantModel->id);

        if (!$subscription) {
            return failResponse(trans('api.no_active_subscription'), code: 403);
        }

        // Expire if needed automatically
        Subscription::expireIfNeeded($subscription);

        // Check again after expiration
        if (!Subscription::isActive($subscription)) {
            return failResponse(trans('api.subscription_expired'), code: 403);
        }

        $tenant->makeCurrent();

        return $next($request);
    }
}
