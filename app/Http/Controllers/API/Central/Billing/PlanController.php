<?php

namespace App\Http\Controllers\API\Central\Billing;

use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Billing\PlanRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Billing\PlanResource;
use App\Models\Central\Plan;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class PlanController extends Controller implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->setDeleteModel(Plan::class);
    }
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-plan'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-plan'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-plan'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Plan::query())
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, PlanResource::class));
    }

    /**
     * @param PlanRequest $request
     * @return JsonResponse
     */
    public function store(PlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return successResponse(new PlanResource($plan), __('api.created_success'));
    }

    /**
     * @param Plan $plan
     * @return JsonResponse
     */
    public function show(Plan $plan): JsonResponse
    {
        return successResponse(new PlanResource($plan));
    }

    /**
     * @param PlanRequest $request
     * @param Plan $plan
     * @return JsonResponse
     */
    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return successResponse(new PlanResource($plan->refresh()), __('api.updated_success'));
    }

}
