<?php

namespace App\Http\Controllers\API\Central\Subscription;

use App\Filters\Central\Subscription\PlanFilter;
use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Subscription\PlanRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Subscription\PlanResource;
use App\Models\Central\Plan;
use App\Tools\Subscription\Facades\Subscription;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;

class PlanController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Plan::class;
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Plan::class);

        $query = app(Pipeline::class)
            ->send(Plan::query()->with(['features', 'prices']))
            ->through([PlanFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(
            fetchData($query, $request->pageSize, PlanResource::class)
        );
    }

    /**
     * @param PlanRequest $request
     * @return JsonResponse
     */
    public function store(PlanRequest $request): JsonResponse
    {
        Gate::authorize('create', Plan::class);

        $plan = Subscription::createFullPlan(
            planData: $request->validated(),
            features: $request->input('features', []),
            prices: $request->input('prices', [])
        );

        return successResponse(new PlanResource($plan), __('api.created_success'));
    }

    /**
     * @param Plan $plan
     * @return JsonResponse
     */
    public function show(Plan $plan): JsonResponse
    {
        Gate::authorize('view', $plan);

        return successResponse(new PlanResource($plan->load(['features', 'prices'])));
    }

    /**
     * @param PlanRequest $request
     * @param Plan $plan
     * @return JsonResponse
     */
    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        Gate::authorize('update', $plan);

        $plan = Subscription::updateFullPlan(
            plan: $plan,
            planData: $request->validated(),
            features: $request->input('features', []),
            prices: $request->input('prices', [])
        );

        return successResponse(
            new PlanResource($plan->refresh()->load(['features', 'prices'])),
            __('api.updated_success')
        );
    }
}
