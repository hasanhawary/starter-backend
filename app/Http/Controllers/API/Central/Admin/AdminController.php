<?php

namespace App\Http\Controllers\API\Central\Admin;

use App\Filters\Central\Admin\AdminFilter;
use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\AdminRequest;
use App\Http\Requests\Central\Global\Other\DeleteAllRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Mail\BasicMail;
use App\Models\Central\Admin;
use App\Models\Central\Country;
use App\Models\Central\Role;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminController extends Controller
{
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
            $admin = Admin::create($this->prepareData($request));
            $this->syncRelations($admin, $request);

            DB::afterCommit(function () use ($admin, $request) {
                $this->sendAdminCredentialsEmail($admin, $request);
            });

            return successResponse(
                new AdminResource($admin->load('roles')),
                __('api.created_success')
            );
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
     * @param AdminRequest $request
     * @param Admin $admin
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(AdminRequest $request, Admin $admin): JsonResponse
    {
        Gate::authorize('update', $admin);

        return DB::transaction(function () use ($admin, $request) {
            $admin->update($this->prepareData($request));
            $this->syncRelations($admin, $request);

            DB::afterCommit(function () use ($admin, $request) {
                $this->sendAdminCredentialsEmail($admin->refresh(), $request);
            });

            return successResponse(new AdminResource($admin->refresh()->load('roles')), __('api.updated_success'));
        });
    }

    /**
     * @param Admin $admin
     * @return JsonResponse
     */
    public function destroy(Admin $admin): JsonResponse
    {
        Gate::authorize('delete', $admin);

        Media::delete($admin->avatar);
        $admin->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    /**
     * @param DeleteAllRequest $request
     * @return JsonResponse
     */
    public function destroyAll(DeleteAllRequest $request): JsonResponse
    {
        Gate::authorize('delete', Admin::class);

        Admin::whereIn('id', $request->ids)->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    /**
     * @param int $id
     * @return JsonResponse
     */

    public function restore(int $id): JsonResponse
    {
        Gate::authorize('restore', Admin::class);

        Admin::onlyTrashed()->findOrFail($id)->restore();

        return successResponse(msg: __('api.restored_success'));
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        Gate::authorize('delete', Admin::class);

        Admin::onlyTrashed()->findOrFail($id)->forceDelete();

        return successResponse(msg: __('api.deleted_success'));
    }

    /**
     * @param Admin $admin
     * @return JsonResponse
     */
    public function changeStatus(Admin $admin): JsonResponse
    {
        $admin->is_active = !$admin->is_active;
        $admin->save();

        return successResponse(msg: __('api.updated_success'));
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

    private function prepareData(AdminRequest $request): array
    {
        return Arr::except($request->validated(), ['permissions', 'roles']);
    }

    private function sendAdminCredentialsEmail(Admin $admin, $request): void
    {
        //TODO::Need to be handled
        $plainPassword = (string)$request->input('password');
        $code = Country::whereKey($request->input('phone_code_id'))->value('phone_code');
        $number = $request->input('phone');

        $fullPhone = trim(($code ?? '') . ($number ?? ''));
        $fullPhone = preg_replace('/\s+/', '', $fullPhone) ?: '---';

        $data = [
            'title' => 'admin_data',
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $fullPhone,
            'plain_password' => $plainPassword,
            'created_at' => now()->format('Y-m-d H:i'),
        ];

        $admin->sendNotification($data,['email']);
    }
}
