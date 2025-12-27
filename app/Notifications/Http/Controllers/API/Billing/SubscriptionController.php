<?php

namespace App\\Notifications\\Http\\Controllers\\API\\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscriptionRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Billing\SubscriptionResource;
use App\Filters\Global\OrderByFilter;
use App\Models\Subscription;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class SubscriptionController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(Subscription::class);
    }
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-subscription'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-subscription'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-subscription'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Subscription::query()->with('plan'))
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SubscriptionResource::class));
    }

    /**
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        $subscription = Subscription::create($request->validated());

        return successResponse(new SubscriptionResource($subscription->load('plan')), __('api.created_success'));
    }

    /**
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load('plan');

        return successResponse(new SubscriptionResource($subscription));
    }

    /**
     * @param SubscriptionRequest $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function update(SubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription->update($request->validated());

        return successResponse(new SubscriptionResource($subscription->refresh()->load('plan')), __('api.updated_success'));
    }

}
