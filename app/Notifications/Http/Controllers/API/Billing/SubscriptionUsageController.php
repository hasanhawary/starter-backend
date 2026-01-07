<?php

namespace App\\Notifications\\Http\\Controllers\\API\\Billing;

use App\Filters\Central\Global\OrderByFilter;use App\Http\Controllers\Controller;use App\Http\Requests\Central\Billing\SubscriptionUsageRequest;use App\Http\Requests\Central\Global\Other\PageRequest;use App\Http\Resources\Central\Billing\SubscriptionUsageResource;use App\Models\Central\SubscriptionUsage;use App\Trait\Global\HasSoftDeleteMethods;use Illuminate\Http\JsonResponse;use Illuminate\Pipeline\Pipeline;use Illuminate\Routing\Controllers\HasMiddleware;use Illuminate\Routing\Controllers\Middleware;use Spatie\Permission\Middleware\PermissionMiddleware;use function __;

class SubscriptionUsageController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(SubscriptionUsage::class);
    }
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-subscription-usage'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-subscription-usage'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-subscription-usage'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(SubscriptionUsage::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SubscriptionUsageResource::class));
    }

    /**
     * @param SubscriptionUsageRequest $request
     * @return JsonResponse
     */
    public function store(SubscriptionUsageRequest $request): JsonResponse
    {
        $usage = SubscriptionUsage::create($request->validated());

        return successResponse(new SubscriptionUsageResource($usage), __('api.created_success'));
    }

    /**
     * @param SubscriptionUsage $subscriptionUsage
     * @return JsonResponse
     */
    public function show(SubscriptionUsage $subscriptionUsage): JsonResponse
    {
        return successResponse(new SubscriptionUsageResource($subscriptionUsage));
    }

    /**
     * @param SubscriptionUsageRequest $request
     * @param SubscriptionUsage $subscriptionUsage
     * @return JsonResponse
     */
    public function update(SubscriptionUsageRequest $request, SubscriptionUsage $subscriptionUsage): JsonResponse
    {
        $subscriptionUsage->update($request->validated());

        return successResponse(new SubscriptionUsageResource($subscriptionUsage->refresh()), __('api.updated_success'));
    }

}
