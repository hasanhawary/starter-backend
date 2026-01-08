<?php

namespace App\Http\Controllers\API\Central\Admin;

use App\Filters\Central\Global\JsonDisplayNameFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\PermissionRequest;
use App\Http\Requests\Central\Global\Other\DeleteAllRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Admin\PermissionResource;
use App\Models\Central\Admin;
use App\Models\Central\Permission;
use App\Trait\Global\HasDeleteMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class PermissionController extends Controller implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->setDeleteModel(Permission::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-permission'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-permission'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-permission'), only: ['update'])
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
            ->through([JsonDisplayNameFilter::class, OrderByFilter::class])
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
}
