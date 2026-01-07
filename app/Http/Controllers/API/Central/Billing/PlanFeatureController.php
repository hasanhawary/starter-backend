<?php

namespace App\Http\Controllers\API\Central\Billing;

use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Billing\PlanFeatureRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Billing\PlanFeatureResource;
use App\Models\Central\PlanFeature;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class PlanFeatureController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(PlanFeature::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-plan-feature'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-plan-feature'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-plan-feature'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(PlanFeature::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, PlanFeatureResource::class));
    }

    /**
     * @param PlanFeatureRequest $request
     * @return JsonResponse
     */
    public function store(PlanFeatureRequest $request): JsonResponse
    {
        $feature = PlanFeature::create($request->validated());

        return successResponse(new PlanFeatureResource($feature), __('api.created_success'));
    }

    /**
     * @param PlanFeature $planFeature
     * @return JsonResponse
     */
    public function show(PlanFeature $planFeature): JsonResponse
    {
        return successResponse(new PlanFeatureResource($planFeature));
    }

    /**
     * @param PlanFeatureRequest $request
     * @param PlanFeature $planFeature
     * @return JsonResponse
     */
    public function update(PlanFeatureRequest $request, PlanFeature $planFeature): JsonResponse
    {
        $planFeature->update($request->validated());

        return successResponse(new PlanFeatureResource($planFeature->refresh()), __('api.updated_success'));
    }
}
