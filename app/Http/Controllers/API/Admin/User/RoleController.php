<?php

namespace App\Http\Controllers\API\Admin\User;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\JsonDisplayNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Admin\User\RoleRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Admin\User\RoleResource;
use App\Models\Role;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;
use function __;

class RoleController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Role::class;

        $this->setDeleteGuards('delete', fn(Role $role) => !$role->roleUsers()->exists())
            ->beforeDelete('delete', fn(Role $role) => $role->permissions()->detach());
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Role::class);

        $roles = app(Pipeline::class)
            ->send(Role::related())
            ->through([JsonDisplayNameFilter::class, ActiveFilter::class, OrderByFilter::class])
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
}
