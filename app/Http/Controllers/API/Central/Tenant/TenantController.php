<?php

namespace App\Http\Controllers\API\Central\Tenant;

use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Filters\Central\Tenant\TenantFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Requests\Central\Tenant\TenantRequest;
use App\Http\Resources\Central\Tenant\TenantResource;
use App\Jobs\Central\SetupTenantJob;
use App\Models\Central\Tenant;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TenantController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Tenant::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-tenant'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-tenant'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-tenant'), only: ['update']),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Tenant::with('creator'))
            ->through([TenantFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, TenantResource::class));
    }

    /**
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function store(TenantRequest $request): JsonResponse
    {
        $tenant = Tenant::create($request->validated());

        SetupTenantJob::dispatch($tenant);

        return successResponse(new TenantResource($tenant), __('api.created_success'));
    }

    /**
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function show(Tenant $tenant): JsonResponse
    {
        return successResponse(new TenantResource($tenant->load('creator')));
    }

    /**
     * @param TenantRequest $request
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function update(TenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());

        return successResponse(new TenantResource($tenant), __('api.updated_success'));
    }
}
