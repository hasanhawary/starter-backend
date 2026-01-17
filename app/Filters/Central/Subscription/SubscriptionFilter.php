<?php

namespace App\Filters\Central\Subscription;

use Closure;

class SubscriptionFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request()->has('search') && !empty(request('search')), function ($query) {
            $query->where(function ($query) {
                $query->where('tenant_id', 'like', '%' . request('search') . '%');
            });
        });

        $query->when(request()->has('status') && !empty(request('status')), function ($query) {
            $query->where('status', request('status'));
        });

        $query->when(request()->has('plan_id') && !empty(request('plan_id')), function ($query) {
            $query->where('plan_id', request('plan_id'));
        });

        return $query;
    }
}
