<?php

namespace App\Http\Controllers\API\User;

use App\Filters\Global\JsonDisplayNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Requests\User\PermissionRequest;
use App\Http\Resources\User\PermissionResource;
use App\Models\Permission;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PermissionController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Permission::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-permission'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-permission'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-permission'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Permission::query())
            ->through([JsonDisplayNameFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, PermissionResource::class));
    }

    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return successResponse(new PermissionResource($permission), __('api.created_success'));
    }

    public function show(Permission $permission): JsonResponse
    {
        return successResponse(new PermissionResource($permission));
    }

    public function update(PermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->validated());

        return successResponse(new PermissionResource($permission->refresh()), __('api.updated_success'));
    }
}
