<?php

namespace App\Http\Controllers\API\Tenant\User;

use App\Filters\Central\Global\JsonDisplayNameFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\RoleRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Admin\RoleResource;
use App\Models\Central\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;
use function __;

class RoleController extends Controller
{
    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Role::class);

        $roles = app(Pipeline::class)
            ->send(Role::related()->with('permissions'))
            ->through([JsonDisplayNameFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($roles, $request->pageSize, RoleResource::class));
    }

    /**
     * @param RoleRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(RoleRequest $request): JsonResponse
    {
        Gate::authorize('create', Role::class);

        return DB::transaction(function () use ($request) {

            $role = Role::create($request->validated());
            $role->syncPermissions($request->permissions);

            return successResponse(new RoleResource($role->load('permissions')), __('api.created_success'));
        });
    }

    /**
     * @param Role $role
     * @return JsonResponse
     */
    public function show(Role $role): JsonResponse
    {
        Gate::authorize('view', $role);

        return successResponse(new RoleResource($role->load('permissions')));
    }

    /**
     * @param RoleRequest $request
     * @param Role $role
     * @return JsonResponse
     * @throws Throwable
     * @throws Throwable
     */
    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        Gate::authorize('update', $role);

        return DB::transaction(function () use ($role, $request) {

            $role->update($request->validated());
            $role->syncPermissions($request->permissions);

            return successResponse(new RoleResource($role->refresh()->load('permissions')), __('api.updated_success'));
        });
    }

    /**
     * @param Role $role
     * @return JsonResponse
     */
    public function destroy(Role $role): JsonResponse
    {
        Gate::authorize('delete', $role);

        if ($role->roleUsers()->exists()) {
            return failResponse(__('api.cant_delete'));
        }

        $role->permissions()->detach();
        $role->deleteQuietly();

        return successResponse(msg: __('api.deleted_success'));
    }
}
