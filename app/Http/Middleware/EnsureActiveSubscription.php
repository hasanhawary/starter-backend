<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Services\Billing\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var SubscriptionService $subscriptionService */
        $subscriptionService = app(SubscriptionService::class);

        /** @var Subscription|null $subscription */
        $subscription = $request->route('subscription');

        if (! $subscription instanceof Subscription) {
            abort(400, 'Subscription not found in route.');
        }

        if (! $subscriptionService->isActive($subscription)) {
            abort(403, 'Subscription is not active.');
        }

        return $next($request);
    }
}
