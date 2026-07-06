# API Controllers, Routes, And Resources

Use this rule when creating or modifying API controllers, routes, API resources, response envelopes, or endpoint contracts.

## Response Envelope

All normal API responses use the project envelope.

```php
successResponse($data, $msg, $code = 200)
// { "status": true, "code": 200, "message": "...", "data": ... }

failResponse($msg, $data = [], $code = 400)
// { "status": false, "code": 400, "message": "...", "data": ... }
```

For lists:

```php
return successResponse(wrapPaginate($query, ProductResource::class));
```

Do not return raw resources directly from controllers unless the same area already intentionally does so.

## Controller: Simple Permission CRUD

This is the current `CountryController` style. Use it for simple data-entry resources where create/update are protected by action middleware and delete/restore/toggle are delegated to traits.

**No service class is needed.** The controller handles `store`/`update` directly with `Model::create()` and `$model->update()` because there is no complex logic, relation syncing, notifications, or transactions.

```php
<?php

namespace App\Http\Controllers\API\DataEntry;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\DataEntry\ProductRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\DataEntry\ProductResource;
use App\Models\Product;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ProductController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Product::class; // Required by delete/restore/toggle traits.
    }

    public static function middleware(): array
    {
        return [
            // Keep action permission names in {action}-{model} format.
            new Middleware(PermissionMiddleware::using('create-product'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-product'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Product::query())
            ->through([JsonNameFilter::class, TrashedFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, ProductResource::class));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return successResponse(new ProductResource($product->refresh()), __('api.created_success'));
    }

    public function show(Product $product): JsonResponse
    {
        return successResponse(new ProductResource($product));
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return successResponse(new ProductResource($product->refresh()), __('api.updated_success'));
    }
}
```

## Controller: Ownership/Service CRUD

This is the current `UserController` style. Use it when ownership, root protection, relation syncing, or notifications require policies/services.

**A service class is required** because the controller has complex logic: relation syncing, `DB::transaction()`, side effects like credential emails, or ownership authorization via `Gate::authorize()`.

```php
class AdminController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct(private readonly AdminService $adminService)
    {
        parent::__construct();
        $this->model = Admin::class;

        // Lifecycle hook keeps file cleanup outside the trait internals.
        $this->beforeDelete('force', fn (Admin $admin) => Media::delete($admin->avatar));
    }

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Admin::class);

        $query = app(Pipeline::class)
            ->send(Admin::with('roles')->related())
            ->through([UserFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, AdminResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(AdminRequest $request): JsonResponse
    {
        Gate::authorize('create', Admin::class);

        $admin = $this->adminService->store($request);

        return successResponse(new AdminResource($admin), __('api.created_success'));
    }

    /**
     * @throws Throwable
     */
    public function update(AdminRequest $request, Admin $admin): JsonResponse
    {
        Gate::authorize('update', $admin);

        $admin = $this->adminService->update($admin, $request);

        return successResponse(new AdminResource($admin), __('api.updated_success'));
    }

    public function show(Admin $admin): JsonResponse
    {
        Gate::authorize('view', $admin);

        return successResponse(new AdminResource($admin->load('roles')));
    }
}
```

## Routes

Protected resources use `auth:sanctum`; trait endpoints are explicit and stay outside `apiResource` destroy.

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::delete('force-delete', [ProductController::class, 'forceDelete']);
        Route::delete('delete', [ProductController::class, 'destroy']);
        Route::post('restore', [ProductController::class, 'restore']);
        Route::put('toggle-active', [ProductController::class, 'toggleActive']);

        Route::apiResource('/', ProductController::class)
            ->parameters(['' => 'product'])
            ->except(['destroy']);
    });
});
```

Define custom routes before resource routes if they could conflict.

## Resource: Translatable Data Entry

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Current translated value for display.
            'translation_name' => $this->name,

            // Full translation object for edit forms.
            'name' => $this->getTranslations('name'),

            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),
            'image' => $this->image,
            'code' => $this->code,
            'created_at' => $this->created_at,
        ];
    }
}
```

## Resource: Relations And Enums

```php
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => [
                'phone' => $this->phone,
                'phone_code' => $this->whenLoaded('phoneCode', fn () => $this->phoneCode?->phone_code, ''),
                'phone_code_id' => $this->phone_code_id,
            ],
            'gender' => $this->gender,
            'display_gender' => UserGenderEnum::resolve($this->gender),
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,

            // Always guard relations with whenLoaded to avoid lazy-loading.
            'roles' => $this->whenLoaded('roles', fn () => BasicResource::collection($this->roles), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'settings' => $this->whenLoaded('settings', fn () => new UserSettingResource($this->settings), ['id' => null]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Resource Rules

- Include only API-safe fields.
- Use `whenLoaded()` for relations.
- For translatable fields, return `translation_{field}` for current-locale value and `{field}` for the full translation object.
- For enums/resolved labels, return `display_{field}` beside the raw field when sibling resources do so.
- Do not query inside resources.
- Keep validation field names, request body keys, and resource output keys aligned with the API contract.
