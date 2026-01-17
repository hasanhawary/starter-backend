<?php

namespace App\Http\Controllers\API\Central\Subscription;

use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Subscription\SubscriptionUsageRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Subscription\SubscriptionUsageResource;
use App\Models\Central\SubscriptionUsage;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;

class SubscriptionUsageController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = SubscriptionUsage::class;
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', SubscriptionUsage::class);

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
        Gate::authorize('create', SubscriptionUsage::class);

        $usage = SubscriptionUsage::create($request->validated());

        return successResponse(new SubscriptionUsageResource($usage), __('api.created_success'));
    }

    /**
     * @param SubscriptionUsage $subscriptionUsage
     * @return JsonResponse
     */
    public function show(SubscriptionUsage $subscriptionUsage): JsonResponse
    {
        Gate::authorize('view', $subscriptionUsage);

        return successResponse(new SubscriptionUsageResource($subscriptionUsage));
    }

    /**
     * @param SubscriptionUsageRequest $request
     * @param SubscriptionUsage $subscriptionUsage
     * @return JsonResponse
     */
    public function update(SubscriptionUsageRequest $request, SubscriptionUsage $subscriptionUsage): JsonResponse
    {
        Gate::authorize('update', $subscriptionUsage);

        $subscriptionUsage->update($request->validated());

        return successResponse(new SubscriptionUsageResource($subscriptionUsage->refresh()), __('api.updated_success'));
    }
}
