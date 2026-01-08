<?php

namespace App\Http\Controllers\API\Central\Tenant;

use App\Filters\Central\Tenant\TenantFilter;
use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Tenant\TenantRequest;
use App\Http\Requests\Central\Global\Other\DeleteAllRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Tenant\TenantResource;
use App\Models\Central\Tenant;
use App\Models\Central\Country;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class TenantController extends Controller
{
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Tenant::class);

        $query = app(Pipeline::class)
            ->send(Tenant::with('creator')->related())
            ->through([TenantFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, TenantResource::class));
    }

    public function store(TenantRequest $request): JsonResponse
    {
        Gate::authorize('create', Tenant::class);

        return DB::transaction(function () use ($request) {
            $tenant = Tenant::create($this->prepareData($request));
            // sync relations if any

            DB::afterCommit(fn () => $this->sendTenantCredentialsEmail($tenant, $request));

            return successResponse(new TenantResource($tenant->load('creator')),
                __('api.created_success'));
        });
    }

    public function show(Tenant $tenant): JsonResponse
    {
        Gate::authorize('view', $tenant);

        return successResponse(new TenantResource($tenant->load('creator')));
    }

    public function update(TenantRequest $request, Tenant $tenant): JsonResponse
    {
        Gate::authorize('update', $tenant);

        return DB::transaction(function () use ($tenant, $request) {
            $tenant->update($this->prepareData($request));
            // sync relations if any

            DB::afterCommit(fn () => $this->sendTenantCredentialsEmail($tenant->refresh(), $request));

            return successResponse(new TenantResource($tenant->refresh()->load('creator')), __('api.updated_success'));
        });
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        Gate::authorize('delete', $tenant);

        Media::delete($tenant->avatar);
        $tenant->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    public function destroyAll(DeleteAllRequest $request): JsonResponse
    {
        Gate::authorize('delete', Tenant::class);

        Tenant::whereIn('id', $request->ids)->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    public function restore(int $id): JsonResponse
    {
        Gate::authorize('restore', Tenant::class);

        Tenant::onlyTrashed()->findOrFail($id)->restore();

        return successResponse(msg: __('api.restored_success'));
    }

    public function forceDelete(int $id): JsonResponse
    {
        Gate::authorize('delete', Tenant::class);

        Tenant::onlyTrashed()->findOrFail($id)->forceDelete();

        return successResponse(msg: __('api.deleted_success'));
    }

    public function changeStatus(Tenant $tenant): JsonResponse
    {
        $tenant->is_active = !$tenant->is_active;
        $tenant->save();

        return successResponse(msg: __('api.updated_success'));
    }

    private function prepareData(TenantRequest $request): array
    {
        return Arr::except($request->validated(), ['permissions', 'roles']);
    }

    private function sendTenantCredentialsEmail(Tenant $tenant, $request): void
    {
        $plainPassword = (string)$request->input('password');
        $code = Country::whereKey($request->input('phone_code_id'))->value('phone_code');
        $number = $request->input('phone');

        $fullPhone = trim(($code ?? '') . ($number ?? ''));
        $fullPhone = preg_replace('/\s+/', '', $fullPhone) ?: '---';

        $data = [
            'title' => 'tenant_data',
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $fullPhone,
            'plain_password' => $plainPassword,
            'created_at' => now()->format('Y-m-d H:i'),
        ];

        $tenant->sendNotification($data, ['email']);
    }
}

