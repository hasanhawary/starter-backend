---
title: Dynamic CLI - CRUD Generator
description: Auto-generate complete CRUD modules with standardized code
---

# Dynamic CLI - CRUD Generator

Dynamic CLI CRUD is a smart, interactive generator for Laravel that creates production-ready CRUD modules (models, controllers, requests, resources, migrations, enums) with automatic schema detection, relations, enums, translatable fields, and file handling.

- For more information, visit [Dynamic CLI on Packagist](https://packagist.org/packages/hasanhawary/dynamic-cli).

## Installation

Included by default; to update or add to another project:

```bash
composer require hasanhawary/dynamic-cli --dev
```

## Generate a CRUD module

```bash
php artisan dynamic:crud Post
```

Outputs a namespaced set under `app/` that immediately works with our pipelines and policies:

| Type | File | Description |
|------|------|-------------|
| Enum | app/Enum/DataEntry/StatusEnum.php | based on  JSON Schema passed when generating |
| Migration | `database/migrations/*create_posts_table.php` | Optional with `--migration`. |
| Model | `Models/Post.php` | Fillable, casts, and role traits when needed. |
| Request | `Http/Requests/PostRequest.php` | Validation skeleton. |
| Controller | `Http/Controllers/API/PostController.php` | Uses `Gate::authorize`, `Pipeline`, and `successResponse` helpers. |
| Filter | `Filters/PostFilter.php` | Hook for query filters used by controller pipelines. |
| Resource | `Http/Resources/PostResource.php` | API transformer aligned with existing resources. |

## Controller style produced

Generated controllers mirror the hand-written ones in this codebase (see `UserController` for reference):

- Authorize with policies (`Gate::authorize`).
- Build queries through Laravel pipelines to layer filters.
- Return `successResponse(wrapPaginate(...))` for list endpoints and resources for single records.
- Support soft deletes when `--soft-delete` is passed (adds scopes in controllers and `SoftDeletes` in models).

## Useful flags

- `--migration` — create the table migration.
- `--with=user,category` — generate relationships and eager loading.
- `--module=Blog` — place files under `app/Modules/Blog` while keeping namespaces consistent.
- `--model-only` or `--model --controller` — generate just the pieces you need.
- `--soft-delete` — wire soft deletes across model and controller scopes.
- `--regenerate --force` — refresh generated sections without touching custom edits.

## After generating

1) Update validation rules in the generated request to match business requirements.

2) Add query filters inside the generated filter class (search, date ranges, relationships). Pipelines expect a `handle` method that returns the builder.

3) Extend the resource to shape responses and eager load needed relations.

4) Register routes if not auto-added: `Route::apiResource('posts', PostController::class);`

## Configuration

Defaults live in `config/dynamic-cli.php` (namespaces, timestamps, soft delete, policies). Publish and edit stubs with:

```bash
php artisan vendor:publish --provider="DynamicCli\Providers\DynamicCliProvider"
```

## Tips

- Keep generated code aligned with your manual controllers—use them as a baseline, not as final logic.
- If you enable soft deletes, remember to surface `withTrashed` and `onlyTrashed` in list filters so UI state stays consistent.
- Pair with [Lookup Manager](/guide/tools/lookup-manager) so new models immediately show up in dynamic forms.
