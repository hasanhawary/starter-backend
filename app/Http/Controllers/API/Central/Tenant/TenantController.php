<?php

namespace App\Http\Controllers\API\Central\Tenant;

use App\Filters\Central\Global\ActiveFilter;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Global\TrashedFilter;
use App\Filters\Central\Tenant\TenantFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Requests\Central\Tenant\TenantRequest;
use App\Http\Resources\Central\Tenant\TenantResource;
use App\Models\Central\Tenant;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TenantController extends Controller
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->setDeleteModel(Tenant::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-tenant'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('update-tenant'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Tenant::with('creator')->related())
            ->through([TenantFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, TenantResource::class));
    }

    public function store(TenantRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            dd($request);
            $tenant = Tenant::create($this->prepareData($request));
            // sync relations if any

            DB::afterCommit(fn() => $this->sendTenantCredentialsEmail($tenant, $request));

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

            DB::afterCommit(fn() => $this->sendTenantCredentialsEmail($tenant->refresh(), $request));

            return successResponse(new TenantResource($tenant->refresh()->load('creator')), __('api.updated_success'));
        });
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
        $code = tenant::whereKey($request->input('phone_code_id'))->value('phone_code');
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

