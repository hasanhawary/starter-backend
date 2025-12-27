<?php

namespace App\Http\Controllers\API\User;

use App\Filters\Global\JsonDisplayNameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Global\Other\DeleteAllRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Requests\User\PermissionRequest;
use App\Http\Resources\User\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class PermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-permission'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-permission'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-permission'), only: ['update']),
            new Middleware(PermissionMiddleware::using('delete-permission'), only: ['destroy', 'destroyAll'])
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Permission::query())
            ->through([JsonDisplayNameFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, PermissionResource::class));
    }

    /**
     * @param PermissionRequest $request
     * @return JsonResponse
     */
    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return successResponse(new PermissionResource($permission), __('api.created_success'));
    }

    /**
     * @param Permission $permission
     * @return JsonResponse
     */
    public function show(Permission $permission): JsonResponse
    {
        return successResponse(new PermissionResource($permission));
    }

    /**
     * @param PermissionRequest $request
     * @param Permission $permission
     * @return JsonResponse
     */
    public function update(PermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->validated());

        return successResponse(new PermissionResource($permission->refresh()), __('api.updated_success'));
    }

    /**
     * @param Permission $permission
     * @return JsonResponse
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    public function destroyAll(DeleteAllRequest $request): JsonResponse
    {
        Permission::whereIn('id', $request->ids)->delete();

        return successResponse(msg: __('api.deleted_success'));
    }
}
