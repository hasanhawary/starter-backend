<?php

namespace App\Http\Controllers\API\Central\Admin;

use App\Filters\Central\Admin\AdminFilter;
use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\AdminRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Models\Central\Admin;
use App\Models\Central\Role;
use App\Trait\Global\HasDeleteMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class AdminController extends Controller
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->setDeleteModel(Admin::class)
            ->beforeDelete('force', fn(Admin $admin) => Media::delete($admin->avatar));
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

        return successResponse(fetchData($query, $request->pageSize, AdminResource::class));
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
     * @param Admin $admin
     * @return JsonResponse
     */
    public function show(Admin $admin): JsonResponse
    {
        Gate::authorize('view', $admin);

        return successResponse(new AdminResource($admin->load('roles')));
    }

    /**
     * @param Admin $admin
     * @return JsonResponse
     */
    public function toggleActive(Admin $admin): JsonResponse
    {
        Gate::authorize('toggle-active', $admin);

        $admin->update(['is_active' => !$admin->is_active]);

        return successResponse(msg: $admin->is_active
            ? __('api.admin_activated')
            : __('api.admin_deactivated')
        );
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
            'title' => $isCreate ? 'admin_data_title' : 'update_admin_data_title',
            'msg' => sprintf(
                $isCreate
                    ? 'admin_data_msg|name=>%s|email=>%s|phone=>%s|password=>%s|created_at=>%s'
                    : 'update_admin_data_msg|name=>%s|email=>%s|phone=>%s|password=>%s|updated_at=>%s',
                $request->name,
                $request->email,
                $admin->getFullPhone(),
                (string)$request->password,
                now()->format('Y-m-d H:i')
            )
        ], ['email']);
    }
}
