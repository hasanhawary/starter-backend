<?php

namespace App\Http\Controllers\API\Central\Subscription;

use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Subscription\PlanFeatureRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Subscription\PlanFeatureResource;
use App\Models\Central\PlanFeature;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;

class PlanFeatureController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = PlanFeature::class;
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', PlanFeature::class);

        $query = app(Pipeline::class)
            ->send(PlanFeature::query())
            ->through([ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(
            wrapPaginate($query, PlanFeatureResource::class)
        );
    }

    /**
     * @param PlanFeatureRequest $request
     * @return JsonResponse
     */
    public function store(PlanFeatureRequest $request): JsonResponse
    {
        Gate::authorize('create', PlanFeature::class);

        $feature = PlanFeature::create($request->validated());

        return successResponse(
            new PlanFeatureResource($feature),
            __('api.created_success')
        );
    }

    /**
     * @param PlanFeature $planFeature
     * @return JsonResponse
     */
    public function show(PlanFeature $planFeature): JsonResponse
    {
        Gate::authorize('view', $planFeature);

        return successResponse(
            new PlanFeatureResource($planFeature)
        );
    }

    /**
     * @param PlanFeatureRequest $request
     * @param PlanFeature $planFeature
     * @return JsonResponse
     */
    public function update(PlanFeatureRequest $request, PlanFeature $planFeature): JsonResponse
    {
        Gate::authorize('update', $planFeature);

        $planFeature->update($request->validated());

        return successResponse(
            new PlanFeatureResource($planFeature->refresh()),
            __('api.updated_success')
        );
    }
}
