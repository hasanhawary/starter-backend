<?php

namespace App\Http\Controllers\API\Admin\User;

use App\Filters\Admin\User\AdminFilter;
use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Admin\User\AdminRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Admin\User\AdminResource;
use App\Models\Admin;
use App\Models\Role;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class AdminController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Admin::class;
        $this->beforeDelete('force', fn(Admin $admin) => Media::delete($admin->avatar));
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Admin::class);

        $query = app(Pipeline::class)
            ->send(Admin::with('roles')->related())
            ->through([AdminFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, AdminResource::class));
    }

    /**
     * @param AdminRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(AdminRequest $request): JsonResponse
    {
        Gate::authorize('create', Admin::class);

        return DB::transaction(function () use ($request) {
            $admin = Admin::create($request->validated());
            $this->syncRelations($admin, $request);

            DB::afterCommit(fn() => $this->sendCredentials($admin, $request));

            return successResponse(new AdminResource($admin->refresh()), __('api.created_success'));
        });
    }

    /**
     * @param AdminRequest $request
     * @param Admin $admin
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(AdminRequest $request, Admin $admin): JsonResponse
    {
        Gate::authorize('update', $admin);

        return DB::transaction(function () use ($admin, $request) {
            $admin->update($request->validated());
            $this->syncRelations($admin, $request);

            DB::afterCommit(fn() => $this->sendCredentials($admin->refresh(), $request));

            return successResponse(new AdminResource($admin->refresh()), __('api.updated_success'));
        });
    }

    /**
     * @param Admin $admin
     * @return JsonResponse
     */
    public function show(Admin $admin): JsonResponse
    {
        Gate::authorize('view', $admin);

        return successResponse(new AdminResource($admin->load('roles')));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function syncRelations(Admin $admin, AdminRequest $request): void
    {
        when($request->filled('roles'), static fn() => $admin->syncRoles(Role::whereId($request->roles)->pluck('name')));
        when($request->filled('permissions'), static fn() => $admin->syncPermissions($request->permissions));
    }

    /**
     * @param Admin $admin
     * @param AdminRequest $request
     * @param bool $isCreate
     * @return void
     */
    private function sendCredentials(Admin $admin, AdminRequest $request, bool $isCreate = true): void
    {
        // Skip update if nothing changed
        if (!$isCreate && !($admin->isDirty('email') || $admin->isDirty('password'))) {
            return;
        }

        $admin->sendNotification([
            'title' => $isCreate ? 'create_admin_data_title' : 'update_admin_data_title',
            'msg' => sprintf(
                $isCreate
                    ? 'create_admin_data_msg|name=>%s|email=>%s|phone=>%s|password=>%s|created_at=>%s'
                    : 'update_admin_data_msg|name=>%s|email=>%s|phone=>%s|password=>%s|updated_at=>%s',
                $request->name,
                $request->email,
                $admin->getFullPhone(),
                (string)$request->password,
                now()->format('Y-m-d H:i')
            )
        ], ['email', 'realtime', 'notify']);
    }
}
