<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Tools\Subscription\Facades\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = Tenant::current()?->id;

        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant not found or not provided.'
            ], 400);
        }

        $subscription = Subscription::getActiveSubscription($tenantId);

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found for this tenant.'
            ], 403);
        }

        // Expire if needed automatically
        Subscription::expireIfNeeded($subscription);

        // Check again after expiration
        if (!Subscription::isActive($subscription)) {
            return response()->json([
                'message' => 'Tenant subscription has expired or cancelled.'
            ], 403);
        }

        // Subscription is active, allow request
        return $next($request);
    }
}
