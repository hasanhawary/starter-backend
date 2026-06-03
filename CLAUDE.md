# CLAUDE.md — Starter Backend

## Project Overview

Laravel 12 REST API with two auth guards (`admin`, `user`), Spatie permissions, LDAP support, media management, activity logging, OTP, export/report builders, and a custom lookup manager.

PHP 8.3+ · Laravel 12 · Sanctum · Spatie Permission · Spatie Translatable · Spatie ActivityLog · LdapRecord

---

## Essential Commands

```bash
# Dev (server + queue + logs + vite concurrently)
composer dev

# Run tests
composer test

# Setup fresh project
composer setup

# Artisan shortcuts
php artisan migrate
php artisan migrate:fresh --seed
php artisan queue:listen --tries=1
php artisan reverb:start          # WebSocket (Laravel Reverb)
```

---

## Directory Structure

```
app/
├── Enum/                         # PHP backed string/int enums
│   ├── Global/                   # Shared enums (ActiveTypeEnum, OtpTypeEnum, …)
│   └── {Domain}/                 # Domain-specific enums
├── Filters/
│   ├── Global/                   # Reusable filters (ActiveFilter, OrderByFilter, …)
│   └── {Guard}/{Domain}/         # Resource-specific filters
├── Http/
│   ├── Controllers/API/
│   │   ├── BaseController.php    # Detects guard, sets Auth::shouldUse()
│   │   ├── Admin/                # Admin-guard controllers
│   │   └── Landing/              # User-guard controllers
│   ├── Requests/
│   │   ├── BaseFormRequest.php   # Normalizes empty→null, formats 422
│   │   ├── Admin/
│   │   └── Global/
│   └── Resources/                # JsonResource classes
├── Models/
│   ├── BaseModel.php             # Extend this for all models
│   └── *.php
├── Rules/                        # Invokable validation rules
├── Scopes/                       # Eloquent scope traits per model
├── Services/                     # Business logic (no HTTP concerns)
│   ├── Auth/                     # Login, OTP, ResetPassword services
│   └── Global/                   # Notification, Setting, QueryHelper
└── Trait/
    └── Global/
        ├── HasDeleteMethods.php      # destroy / restore / forceDelete (bulk)
        ├── HasToggleActiveMethods.php # toggleActive (bulk)
        ├── HasOrder.php              # changeOrder
        ├── LogsActivityOptions.php   # Spatie activity log defaults
        ├── CreatedByObserver.php     # Auto-sets created_by on create
        └── ApplyNotification.php     # sendNotification(data, types[])

Modules/                          # Optional module namespace (Modules\Export\…)
database/
├── factories/                    # All models must have a factory
└── migrations/
```

---

## Auth & Guards

Two guards: `admin` (Admin model) and `user` (User model). Guard is auto-detected via `detectGuard()` based on route prefix (`api/admin/*` → admin, `api/*` → user).

```php
// BaseController boots this automatically — never set guard manually in controllers
Auth::shouldUse($this->guard);

// Route middleware
Route::middleware(['auth:admin', 'ability:admin'])->group(...)
Route::middleware(['auth:user',  'ability:user'])->group(...)
```

---

## Packages & Their Usage

| Package | Purpose | Key API |
|---|---|---|
| `hasanhawary/media-manager` | File upload/delete/URL | `Media::replace($old)->upload($file, 'folder')` · `Media::url($path)` · `Media::delete($path)` |
| `hasanhawary/permission-manager` | Registers permissions from model flags | `$model->inPermission = true` · `$model->specialOperations = [...]` |
| `hasanhawary/lookup-manager` | Enum-to-frontend metadata | `EnumMethods` trait on enums · `keyName()` for custom key |
| `hasanhawary/export-builder` | Excel/CSV export | `ExportController` via `GET /export?model=X` |
| `hasanhawary/report-builder` | Dynamic chart reports | `ReportController` |
| `spatie/laravel-permission` | RBAC | `HasRoles` on models · `PermissionMiddleware::using(...)` |
| `spatie/laravel-translatable` | JSON-column translations | `HasTranslations` · `$translatable = ['name']` |
| `spatie/laravel-activitylog` | Audit log | `LogsActivityOptions` trait |
| `directorytree/ldaprecord-laravel` | LDAP/AD auth | Configured in `config/project.php` → `ldap` |
| `laravel/reverb` | WebSocket broadcasting | `php artisan reverb:start` |
| `maatwebsite/excel` | Excel export | Used internally by export-builder |

---

## Conventions (Non-Negotiable)

### Enums
- PHP backed string enum in `app/Enum/{Domain}/XxxEnum.php`
- Always use `EnumMethods` trait from `hasanhawary/lookup-manager`
- Migration column type is **`string`** (never `enum`)
- Cast in model `$casts`, validate via `Rule::enum(XxxEnum::class)`

### Controllers
- Extend `BaseController` (handles guard detection automatically)
- Thin: only HTTP concerns — delegate everything to Service or use `Pipeline`
- `index()` always uses `Illuminate\Pipeline\Pipeline` with filter classes
- Use traits `HasDeleteMethods` + `HasToggleActiveMethods` for standard operations
- Set `$this->model = ModelClass::class` in constructor when using traits
- Use `Gate::authorize()` for policy checks; `PermissionMiddleware::using()` for route-level
- Permissions middleware goes in `static function middleware(): array` (implements `HasMiddleware`)

### Requests
- Always extend `BaseFormRequest` (not `FormRequest`)
- `prepareForValidation()` auto-normalizes empty strings → null
- Custom rules as invokable classes in `app/Rules/`
- Translatable fields: use `TranslatableRequired` or `TranslatableNullable` rules
- Unique translatable: use `UniqueCheck` rule

### Models
- Extend `BaseModel`
- Always declare `$fillable` (never `$guarded`)
- Always declare `$casts` for all non-string columns and enums
- Return-typed relationships
- Scopes in separate `Scopes/{Domain}/XxxScopes.php` trait — prefix `scope`
- Media: use custom `Attribute::make()` getter with `Media::url()` + mutator setter
- Traits to use: `LogsActivityOptions`, `CreatedByObserver`, `ApplyNotification` as needed
- `$model->inPermission = true` registers the model with permission-manager
- `$model->specialOperations = ['restore', 'force-delete', 'toggle-active']` for extra permissions

### Migrations
- Additive and reversible
- Column type for enums: **`string`**
- Index every FK and every column used in filter/sort/search
- Use `foreignIdFor(ModelClass::class)` for FKs
- Always include `softDeletes()` for resources with delete/restore
- Always include `timestamps()`

### Services
- Own all business logic
- Wrap multi-step writes in `DB::transaction()`
- Use `DB::afterCommit()` for side-effects (notifications, jobs)
- Return Eloquent models (not arrays)
- Dispatch events/jobs from service, not controller

### Filters
- One responsibility per filter class
- Signature: `handle($query, Closure $next)` — receives the **query builder**, not the request
- Access request params via `request()` helper inside the filter
- Place reusable filters in `app/Filters/Global/`
- Place resource-specific filters in `app/Filters/{Guard}/{Domain}/`

### Resources
- Standard `JsonResource` in `app/Http/Resources/{Guard}/{Domain}/XxxResource.php`
- Always expose `id`, `created_at`, and relevant translated fields

### Factories
- Required for every model
- Use realistic Faker data
- Use `state()` methods for variants (e.g. `->inactive()`, `->withAvatar()`)

### Responses (Global Helpers)
```php
successResponse($data, $msg, $code = 200)   // { status, code, message, data }
failResponse($msg, $data, $code = 400)       // { status, code, message, data }
wrapPaginate($query, ResourceClass::class)   // respects ?per_page=-1 (all) or per_page=N
abort403($condition)                          // conditional 403
```

### Routes
```php
// Standard pattern for a resource with soft-delete and toggle:
Route::prefix('items')->group(function () {
    Route::delete('force-delete', [ItemController::class, 'forceDelete']);
    Route::delete('delete',       [ItemController::class, 'destroy']);
    Route::post('restore',        [ItemController::class, 'restore']);
    Route::put('toggle-active',   [ItemController::class, 'toggleActive']);
    Route::apiResource('/', ItemController::class)
        ->parameters(['' => 'item'])
        ->except(['destroy']);
});
```

---

## Skills — Read Before Acting

| Task | Skill file |
|---|---|
| Creating a new API resource (CRUD) | `.claude/skills/crud-resource.md` |
| Adding a filter class | `.claude/skills/filters.md` |
| Adding media to a model | `.claude/skills/media.md` |
| Working with translatable fields | `.claude/skills/translatable.md` |
| Adding permissions to a resource | `.claude/skills/permissions.md` |
| Writing or fixing a migration | `.claude/skills/migrations.md` |

---

## Hard Rules

- No `enum` column type in migrations
- No business logic in controllers or models
- No inline validation — always `FormRequest`
- No `$guarded = []` — always explicit `$fillable`
- No raw SQL where Eloquent works
- No `provide/inject` for shared state (use Service classes or Laravel container)
- Filters receive the **query builder** (not the request) as the first pipe argument
- `BaseFormRequest` always — never plain `FormRequest`
