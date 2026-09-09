# Modules, Feature Scaffolding, And API Contracts

Use this rule when creating a new module/feature, adding fields, changing endpoint contracts, or aligning backend responses with clients.

## Feature Scaffolding Flow

Before creating a new feature or module, inspect existing `app/` and `Modules/` files. The repository has a `Modules/` structure and `Modules\` autoload, but do not assume a module generator command unless it exists in the current project.

For CRUD/API features, generate or update the relevant set:

1. Model extending `BaseModel` or a justified vendor/auth base.
2. Migration with indexes, foreign keys, timestamps, optional soft deletes.
3. Form Request extending `BaseFormRequest`.
4. Resource extending `JsonResource`.
5. Controller extending `BaseController` using Pipeline, response helpers, authorization, and delete/toggle traits when needed.
6. Routes under `auth:sanctum` with resource and custom endpoints.
7. Pipeline filter classes, reusing global filters first.
8. Policy for resource-level authorization when needed.
9. Service for non-trivial writes or multi-step business logic.
10. Factory/seeder for test/demo data when needed.
11. PHPUnit feature tests for happy path, validation failure, unauthenticated, unauthorized, and authorized cases.
12. Translations in the existing `lang` structure for user-facing messages.

## Module Paths

Use existing module conventions if working inside `Modules/{Name}/`. Typical paths are:

```text
Modules/{Name}/
  app/Http/Controllers/...
  app/Http/Requests/...
  app/Http/Resources/...
  app/Models/...
  app/Observers/...
  app/Services/...
  database/migrations/...
  database/seeders/...
  routes/api.php
  config/...
  lang/{ar,en}/...
```

Match the exact namespace casing and path convention used by the target module.

## API Contract Alignment

This skill is backend-only, but endpoints must stay stable for API consumers.

- Route prefix must match the expected endpoint name.
- Request keys must match form/API payload keys.
- Resource keys must match table/header/detail expectations.
- Search/filter query params must match Pipeline request keys.
- Sort params follow existing `sort_column`/`sort_direction` patterns.
- List endpoints return paginated envelope data through `wrapPaginate()`.
- Validation errors use Laravel 422 field-key errors so clients can map errors to fields.
- If adding fields, update migration, model fillable/casts, request rules, resource output, filters if searchable, tests, and translations together.

## Route Pattern

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

## Contract Checklist

```text
[ ] Route prefix matches expected endpoint.
[ ] Form Request keys match incoming payload keys.
[ ] Resource output keys match consumer keys.
[ ] Relation values use nested resources or existing relation shape.
[ ] Translatable fields return translation_{field} and full translation object.
[ ] Enum fields return raw and display fields when needed.
[ ] Search/filter params exist in Pipeline filters.
[ ] List endpoint uses wrapPaginate().
[ ] 422 errors expose field keys.
```
