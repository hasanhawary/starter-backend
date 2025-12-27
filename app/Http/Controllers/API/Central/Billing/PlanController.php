<?php

namespace App\Http\Controllers\API\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PlanRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Billing\PlanResource;
use App\Filters\Global\OrderByFilter;
use App\Models\Plan;
use App\Trait\Global\HasSoftDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class PlanController extends Controller implements HasMiddleware
{
    use HasSoftDeleteMethods;

    public function __construct()
    {
        $this->setSoftDeleteModel(Plan::class);
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
