# Developer Guide — Starter Backend

A complete walkthrough of this Laravel API backend: every architectural layer, every shared class, every trait, and every feature — written for a developer who has just cloned the repository and needs to understand how it works before writing a line of code.

> **Related documents**
> - `README.md` — product-level overview and feature highlights.
> - `AGENTS.md` — the control plane: skill registry, routing table, change discipline, verification rules. **Read it before making changes.**
> - `.agents/skills/` — the authoritative convention registry (one skill per topic).
> - `documentation/` — a VitePress site with per-topic guides.
>
> This guide explains **what exists and how it works**. `AGENTS.md` + `.agents/skills/` explain **how you are expected to change it**.

---

## Table of Contents

1. [What This Project Is](#1-what-this-project-is)
2. [Technology Stack](#2-technology-stack)
3. [Directory Layout](#3-directory-layout)
4. [Request Lifecycle](#4-request-lifecycle)
5. [Bootstrapping & Service Providers](#5-bootstrapping--service-providers)
6. [Configuration Files](#6-configuration-files)
7. [Base Classes](#7-base-classes)
8. [Models](#8-models)
9. [Traits — The Behavioral Toolkit](#9-traits--the-behavioral-toolkit)
10. [Query Scopes](#10-query-scopes)
11. [Filters & The Pipeline Pattern](#11-filters--the-pipeline-pattern)
12. [Services](#12-services)
13. [Global Helper Functions](#13-global-helper-functions)
14. [Validation: Form Requests & Custom Rules](#14-validation-form-requests--custom-rules)
15. [Enums](#15-enums)
16. [API Resources](#16-api-resources)
17. [Authorization: Policies, Roles & Permissions](#17-authorization-policies-roles--permissions)
18. [Authentication & Sessions](#18-authentication--sessions)
19. [Exceptions & Error Handling](#19-exceptions--error-handling)
20. [Notifications, Events, Jobs & Mail](#20-notifications-events-jobs--mail)
21. [Settings System](#21-settings-system)
22. [Activity Logging](#22-activity-logging)
23. [Reports & Exports](#23-reports--exports)
24. [Lookup / Help Endpoints & Discovery Config](#24-lookup--help-endpoints--discovery-config)
25. [Media & Chunked Uploads](#25-media--chunked-uploads)
26. [Localization & Translatable Content](#26-localization--translatable-content)
27. [Feature Modules](#27-feature-modules)
28. [API Endpoint Reference](#28-api-endpoint-reference)
29. [Console Commands & Installation](#29-console-commands--installation)
30. [Testing](#30-testing)
31. [Recipes: How To Add Things](#31-recipes-how-to-add-things)

---

## 1. What This Project Is

An **API-only Laravel backend** built as the base template for admin dashboards and internal management systems. There is no frontend in this repository; every endpoint returns JSON under `/api`.

Key characteristics:

- **Single-instance, admin-only.** One database, one flat API namespace, one `User` model. No multi-tenant or customer-facing split.
- **Token authentication** with Laravel Sanctum (custom guard and token model).
- **RBAC** through `spatie/laravel-permission`, wrapped by a permission-manager package that seeds roles and permissions from config.
- **Convention-heavy.** A large amount of behavior is shared through traits, filters, helper functions, and configuration rather than repeated per resource.
- **Modular.** Optional feature modules (`Modules/Form`, `Modules/Notification`) live under `Modules/` via `nwidart/laravel-modules`.

---

## 2. Technology Stack

| Layer | Choice |
|---|---|
| Runtime | PHP 8.4 / 8.5 |
| Framework | Laravel 13 |
| Auth tokens | Laravel Sanctum 4 (custom guard) |
| Realtime | Laravel Reverb 1 (WebSockets) |
| Permissions | `spatie/laravel-permission` 6 |
| Activity audit | `spatie/laravel-activitylog` 5 |
| Translations | `spatie/laravel-translatable` 6 |
| Modules | `nwidart/laravel-modules` 13 |
| Directory auth | `directorytree/ldaprecord-laravel` 4 |
| Excel/CSV | `maatwebsite/excel` 3 |
| Monitoring | Laravel Pulse, `opcodesio/log-viewer` |
| Tests | PHPUnit 12 |
| Formatting | Laravel Pint |

### First-party custom packages

Six packages by the same author supply large chunks of behavior. They are **the reason many features "just work" with only a config entry**:

| Package | Role in this project |
|---|---|
| `hasanhawary/media-manager` | `Media` facade — upload, replace, delete, signed URLs, chunked transfer (`ChunkResolver`). |
| `hasanhawary/permission-manager` | Seeds roles/permissions from `config/roles.php` based on model flags (`$inPermission`, `$basicOperations`, `$specialOperations`). |
| `hasanhawary/lookup-manager` | `Lookup` facade behind `help-models` / `help-enums` / `help-configs`; also ships the `EnumMethods` trait used by every enum. |
| `hasanhawary/report-builder` | `BaseReport` + `ReportBuilder` — dashboard cards/charts/tables resolved by page name. |
| `hasanhawary/export-builder` | `BaseExport` — queued/chunked Excel, CSV and PDF exports with signed download URLs. |
| `hasanhawary/dynamic-cli` | CRUD scaffolding generator (referenced by the CRUD skill). |

---

## 3. Directory Layout

```
app/
├── Console/Commands/        Setup.php — the app:install installer
├── Enum/
│   ├── Global/              Cross-cutting enums (OTP type, setting type, report type…)
│   └── User/                User-scoped enums (gender)
├── Events/                  NotificationEvent (broadcast)
├── Exceptions/              Domain exceptions rendered by bootstrap/app.php
├── Filters/
│   ├── BaseFilter.php       Shared filter helpers
│   ├── Global/              Reusable pipeline filters (search, active, trashed, order-by…)
│   ├── Notification/  Setting/  User/     Feature-specific filters
├── Guards/                  SanctumGuard — custom token validity rules
├── Helpers/                 App.php (global functions) + DelimiterParamValue
├── Http/
│   ├── Controllers/API/     Auth/ DataEntry/ Global/{Feature}/ Profile/ User/
│   ├── Middleware/          LanguageMiddleware, ValidateAIToken
│   ├── Requests/            BaseFormRequest + grouped Form Requests
│   └── Resources/           JSON projections, grouped by feature
├── Jobs/                    SendEmailJob, SendSmsJob
├── Mail/                    BasicMail (queued), BasicMailWithoutQueue
├── Models/                  Flat — every model extends BaseModel or a vendor base
├── Notifications/           UserNotify (database channel)
├── Policies/User/           UserPolicy, RolePolicy
├── Providers/               AppServiceProvider, ExtendedSanctumServiceProvider
├── Rules/                   Custom validation rules
├── Scopes/User/             Reusable query-scope traits
├── Services/
│   ├── Auth/                Login, OTP, ResetPassword, Throttle
│   ├── Global/              Setting, Notification, Encryption, QueryHelper, DiscoveryConfigResolver
│   └── User/                UserService
├── Tools/
│   ├── Export/              UserExport (Export Builder definitions)
│   └── Report/              UserReport (Report Builder definitions)
└── Trait/Global/            The shared behavior toolkit (see §9)

Modules/
├── Form/                    Dynamic form builder module
└── Notification/            Notification event engine module

config/       30 config files — project.php, discovery.php, roles.php, report.php, export.php, lookup.php …
database/     migrations, seeders, brands/
lang/         en/ ar/ translation files
routes/       api.php, web.php, channels.php, console.php
tests/        Feature/ + Unit/
```

### Naming conventions that matter

- **Singular namespaces for shared code**: `app/Enum`, `app/Trait`, `app/Filters`, `app/Scopes`, `app/Rules`.
- **`Global/` sub-namespace** = cross-cutting, reusable anywhere.
- **`DataEntry/`** = reference/lookup data (countries and similar), consistently across controllers, requests, resources and filters.
- **Models stay flat** in `app/Models`; only the layers above them are grouped by feature.
- **Feature modules mirror the root layout** inside `Modules/{Name}/app/`.

---

## 4. Request Lifecycle

```
HTTP request
   │
   ├─ LanguageMiddleware        reads Accept-Language (en|ar), sets app locale (default: ar)
   ├─ auth:sanctum              custom SanctumGuard validates the personal access token
   ├─ PermissionMiddleware      (per-controller, via HasMiddleware::middleware())
   │
   ├─ Form Request              BaseFormRequest normalizes empties → null, then validates
   │                            failedValidation() returns { message, errors } with 422
   │
   ├─ Controller                thin: authorize → build query / call service → wrap response
   │     │
   │     ├─ Pipeline            Model::query() piped through filter classes
   │     ├─ Service             transactional business logic
   │     └─ Trait actions       destroy / restore / forceDelete / toggleActive / pin / file ops
   │
   ├─ API Resource              JSON projection
   ├─ wrapPaginate()/fetchData()  pagination + `sorting` metadata
   └─ successResponse()         { status, code, message, data }
```

Errors short-circuit to the render handlers registered in `bootstrap/app.php` (§19), which produce the same envelope with `status: false`.

### The response envelope

Every endpoint answers with one of these two shapes:

```json
// successResponse($data, $msg, $code = 200)
{ "status": true,  "code": 200, "message": "…", "data": { } }

// failResponse($msg, $data, $code = 400)
{ "status": false, "code": 400, "message": "…", "data": [] }
```

Validation failures are the one exception — they return `{ "message": "<first error>", "errors": { … } }` with 422, because that is what the frontend form layer expects.

---

## 5. Bootstrapping & Service Providers

### `bootstrap/app.php`

Configures routing (`api`, `web`, `channels`, `console`, health check at `/up`), prepends `LanguageMiddleware` to the API stack, and registers **all exception renderers** (§19). It also calls `$app->useLangPath(base_path('lang'))`.

### `AppServiceProvider`

```php
Model::preventLazyLoading(! app()->isProduction());   // N+1 guard outside production
Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

// Rebinds the LookupManager config resolver so `discovery` labels are translated per request
$this->app->singleton(ConfigLookupManager::class, fn ($app) => new ConfigLookupManager(
    configResolver: $app->make(DiscoveryConfigResolver::class),
));

Gate::policy(Role::class, RolePolicy::class);
Gate::policy(User::class, UserPolicy::class);
```

Two things worth remembering:
- **Lazy loading throws outside production.** If you get a `LazyLoadingViolationException`, add the missing `with()` / `load()` — do not disable the guard.
- The `ConfigLookupManager` rebind happens in `boot()` deliberately, so it wins over the package's own binding.

### `ExtendedSanctumServiceProvider`

Extends Sanctum's provider purely to swap in `App\Guards\SanctumGuard` when creating the request guard.

### `App\Guards\SanctumGuard`

Overrides `isValidAccessToken()`. A token is valid when the configured `sanctum.expiration` window has not elapsed since `last_used_at` (falling back to `created_at`). The provider-matching check from the base class is intentionally commented out; Sanctum's `$accessTokenAuthenticationCallback` is still honored.

### Middleware

| Class | Purpose |
|---|---|
| `LanguageMiddleware` | Reads the `Accept-Language` header; accepts only `en` / `ar`; **defaults to `ar`**. Prepended to every API request. |
| `ValidateAIToken` | Guards AI/automation endpoints by comparing the `X-Token` header against `config('token.aiToken')`; aborts 403 on mismatch. |

---

## 6. Configuration Files

Beyond Laravel's defaults, these carry project-specific meaning:

| File | What it controls |
|---|---|
| `config/project.php` | The central knob board: project metadata, login methods, encryption of outgoing payloads, OTP behavior, throttling, pagination defaults, upload limits, realtime toggle. |
| `config/discovery.php` | Frontend-facing **filter form definitions** and **sortable column maps**, per module. Consumed through `help-configs`. |
| `config/roles.php` | Role → permission map for the permission-manager seeder, plus `additional_operations` and the default guard (`api`). |
| `config/report.php` | Report pages (cards/charts/tables with grid sizes), namespace `App\Tools\Report`, card icons, error strategy. |
| `config/export.php` | Export namespace `App\Tools\Export`, chunk size, PDF settings, package route/permission module. |
| `config/lookup.php` | Lookup access control: allowed models, blocked columns, name-field fallbacks. Package routes disabled — the app owns them. |
| `config/lang.php` | `languages_validation` — which locales are required vs optional for translatable fields. |
| `config/brands.php` | Multi-brand selection (`default_brand`), used to key the settings cache. |
| `config/ldap.php` | LDAP connection + `active` / `local` switches. |
| `config/modules.php` | Module paths and generator stubs (no views/assets — API-only). |
| `config/setup.php`, `config/chart.php`, `config/permission.php`, `config/pulse.php`, `config/reverb.php` | Installer defaults, chart presets, Spatie permission tables, Pulse, Reverb. |

#### `config/project.php` highlights

```php
'auth' => [
    'login_methods'  => ['password' => true, 'otp' => env('AUTH_LOGIN_OTP', false)],
    'encryption'     => [
        'key'      => env('FRONT_SHARED_KEY'),
        'incoming' => ['password' => false, 'otp' => false],
        'outgoing' => ['roles' => true, 'permissions' => true, 'token' => true, 'user_data' => false],
    ],
    'otp'                 => ['required_for' => ['admin' => false, 'user' => false], 'fallback_to_password' => true],
    'max_login_attempts'  => 5,
    'lockout_time'        => 180,   // seconds
    'default_role'        => 'default_role',
    'strong_password'     => …,
],
'otp'        => ['default' => '1111', 'length' => 6, 'type' => 'alpha', 'delay' => 30, 'expires_in' => 10, 'max_attempts' => 5, 'lock_time' => 120],
'pagination' => ['per_page' => 10, 'max' => 1000],
'realtime'   => ['enabled' => env('REALTIME_ENABLED', true)],
```

> ⚠️ `otp.default` forces a fixed OTP (`1111`) — a development convenience. Set it to `null` before any real deployment.

---

## 7. Base Classes

### `App\Models\BaseModel`

An intentionally empty abstract `Model`. Every application model extends it (unless it must extend a vendor base such as `SpatieRole` or `Authenticatable`), giving one place to add global model behavior later.

### `App\Http\Controllers\API\BaseController`

```php
abstract class BaseController
{
    protected ?string $guard;
    protected ?string $userModel;

    public function __construct()
    {
        $this->guard = config('auth.defaults.guard');
        Auth::shouldUse($this->guard);
        $provider        = config("auth.guards.{$this->guard}.provider");
        $this->userModel = config("auth.providers.$provider.model");
    }
}
```

It resolves the active guard and the user model class from config, so auth services can be pointed at the right model without hardcoding `User::class`. **Any subclass with its own constructor must call `parent::__construct()`.**

### `App\Http\Requests\BaseFormRequest`

Three responsibilities:

1. **`prepareForValidation()`** — recursively maps every input through `resolveEmptyToNull()`, so `""`, `"null"`, and `[]` all become `null`. Subclasses that override it must call `parent::prepareForValidation()`.
2. **`booleanInput(string $key, bool $default = false)`** — normalizes the many boolean shapes a frontend may send (`1`, `0`, `"true"`, `true`, …).
3. **`failedValidation()`** — throws an `HttpResponseException` with `{ message: <first error>, errors: <bag> }` at 422 instead of Laravel's default redirect/response.

### `App\Filters\BaseFilter`

Shared helpers for filter classes:

| Method | Behavior |
|---|---|
| `normalizeAdvancedFilters(?array)` | Flattens one level of nested `value` arrays so grouped multi-selects collapse into a flat union of values. |
| `applyDateRangeFilter($query, $key, ?$column)` | Applies `{key}_from` / `{key}_to` as **two independent comparisons**, so either bound may be sent alone. Auto-qualifies the column to survive joins. |
| `applyNameFilter($query, $term)` | Case-insensitive `LOWER(name) LIKE LOWER(?)`. |
| `applySmartNameFilter($query, $term)` | Matches `name`, or `CONCAT(first_name, ' ', last_name)` — whichever the table actually has. |

### `App\Services\Auth\BaseAuthService`

Fluent base for auth services: `setModel()`, `getModel()`, `setGuard()`, `getGuard()`, and `resolveUser(string $email)` which throws `InvalidOtpException` when the email is not registered.

---

## 8. Models

All models live flat in `app/Models`.

### Permission-manager model flags

The permission seeder reads these public properties off each model:

| Property | Meaning |
|---|---|
| `public bool $inPermission` | Include this model when generating permissions. |
| `public array $basicOperations` | Which of `create`/`read`/`update`/`delete` to generate. |
| `public array $specialOperations` | Extra abilities: `view-all`, `view-own`, `restore`, `force-delete`, `toggle-active`. |

Permission names are built as `{operation}-{kebab-model}` — e.g. `view-all-user`, `toggle-active-country`, `force-delete-user`.

### `User`

```php
class User extends Authenticatable implements LdapAuthenticatable
{
    use ApplyNotification, AuthenticatesWithLdap, CreatedByObserver, HasApiTokens,
        HasFactory, HasRoles, InteractsWithSockets, LogsActivityOptions,
        Notifiable, SoftDeletes, UserScopes;
```

- `guard_name = 'api'`; `$with = ['phoneCode']` (always eager-loaded).
- Casts: `is_active` bool, `password` hashed, `gender` → `UserGenderEnum`, `otp_data` array, `last_login`/`email_verified_at` datetime.
- `avatar` accessor runs through `Media::url()`; `password` mutator bcrypts.
- `getFullPhone()` concatenates the country's `phone_code` with `phone`, stripped of whitespace, or `'---'`.
- Relations: `creator()` (self, via `created_by`), `phoneCode()` → `Country`, `settings()` → `UserSetting`.
- Overrides `getActivitylogOptions()` to log only dirty fillable attributes.
- LDAP fields: `ldap_name`, `guid`, `uid`.

### `Role` / `Permission`

Extend the Spatie models. Both use `HasTranslations` on `display_name`. `Role` adds `CreatedByObserver`, `RoleScopes`, a `creator()` relation, and `roleUsers()` (a `morphedByMany` into `model_has_roles`).

### `Country`

Reference data. `HasTranslations` on `name` and `nationality`; `SoftDeletes`; `flag` is stored through `Media::replace()->upload(…, 'flags')` on set and returned as `Media::url()` on get; `scopeActive()`.

### `Setting`

Key/value configuration rows grouped by dotted `group` paths.
- `type` casts to `SettingTypeEnum`; `label` and `placeholder` are translatable; `is_multi_lang` and `is_env` are booleans.
- The `value` attribute is cast on read by `castValue()`: checkbox/radio → boolean, `imageUploader`/`file` → `Media::url()`, JSON strings → decoded arrays. On write, arrays are JSON-encoded.
- `scopePublic()` excludes `is_env` rows — authenticated `GET /api/settings` only returns public settings.

### `Notification`

Reads Laravel's `notifications` table with `HasUuids`, `data` cast to array, and `scopeForCurrentUser()` scoping to `notifiable_type = User::class` + the authenticated id. Adds an `open_at` column alongside `read_at` (opened = the bell was viewed; read = the item was clicked).

### `PersonalAccessToken`

Extends Sanctum's model and adds a `meta` JSON column, which `LoginService` fills with device/browser/OS, platform, timezone, language, screen size, IP and user agent — this is what powers the "active sessions" list in `GET /api/me`.

### `UserSetting`

Per-user JSON preferences (`setting` cast to array), `hasOne` from `User`, excluded from permission generation.

---

## 9. Traits — The Behavioral Toolkit

Everything in `app/Trait/Global`. Traits fall into two families: **controller action traits** (they add HTTP endpoints) and **model behavior traits**.

### 9.1 Controller action traits

#### `HasDeleteMethods`

Adds `destroy()`, `restore()` and `forceDelete()` to a controller. Wire it up in the constructor:

```php
class UserController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn (User $user) => Media::delete($user->avatar));
    }
}
```

**Configuration API** (all fluent, all `protected`):

| Method | Purpose |
|---|---|
| `setDeleteModel(string $model)` | Alternative to assigning `$this->model`. |
| `enableDeletePolicy(bool)` | Turn the policy check off (default on). |
| `setDeleteGuards(string $action, callable\|array)` | Extra predicates; returning `false` aborts 403. |
| `beforeDelete(string $action, callable\|array)` | Hooks run before the action. |
| `afterDelete(string $action, callable\|array)` | Hooks run after the action. |

`$action` is one of `delete`, `restore`, `force`.

**Execution order per record** (`handle()`):
1. `resolveDeleteIds()` — reads `ids`, then `id`, then the first route parameter (unwrapping a route-model-bound instance). Always returns an array, so **bulk and single delete share one code path**.
2. `buildDeleteQuery()` — for `restore`/`force` on a soft-deleting model, scopes to `onlyTrashed()`.
3. Empty result → `failResponse(__('api.record_not_found'))`.
4. **Policy check** (`applyDeleteAuthorize`): `root` bypasses everything; otherwise `Gate::authorize()` when a policy exists, else a fallback `can("{$ability}-{$kebabModel}")` check (using `can()`, not `hasPermissionTo()`, so an undefined permission gives 403 instead of throwing). `force` maps to the `force-delete` ability.
5. **Custom guards** → 403 with `api.not_allowed_to_{$action}`.
6. **`guardLinkedRelations()`** — if the model defines `preventDeleteRelations(): array`, each listed relation is checked with `exists()` and blocks the delete. Two shapes are supported:
   ```php
   public function preventDeleteRelations(): array
   {
       return ['contracts', 'causes' => 'not_allowed_to_delete_linked_cause'];
   }
   ```
   A plain list uses the default message key `not_allowed_to_delete_linked`; a map overrides it per relation.
7. Before callbacks → execute → after callbacks. `Role` is deleted with `deleteQuietly()` (Spatie cache side-effects).
8. Response: restored records are echoed back (single object when one, list when many); deletes return an empty payload.

#### `HasToggleActiveMethods`

Adds `toggleActive()`. Same shape: `setToggleModel()`, `enableTogglePolicy()`, `setToggleGuards()`, `beforeToggle()`, `afterToggle()`.

It first verifies the table actually has an `is_active` column (otherwise `api.model_not_support_toggle`), authorizes with the `toggle-active` ability (policy first, permission fallback), flips the boolean per record, and returns `model_activated` / `model_deactivated` based on the **last** record's new state.

#### `HasFileActionsMethods`

Adds `deleteFile()` and `replaceFile()` for models that represent a stored file.

| Method | Purpose |
|---|---|
| `setStoragePath(string)` | Destination folder for replacements. |
| `setModel(string)` | File model class. |
| `enableFilePolicy(bool)` | Toggle authorization. |
| `setGateConfig(string $action, string $ability, $model = null)` | Explicit gate instead of the derived permission. |
| `setReplaceFields(array)` | Which columns a replace updates — defaults to `['name', 'path']`. |
| `setFileGuard()` / `beforeFileAction()` / `afterFileAction()` | Guards and hooks. |

Delete calls `Media::delete($file->getRawOriginal('path'))` then deletes the row. Replace calls `Media::replace(old)` then `Media::from($newFile)->to($storagePath)->store()`. Success messages resolve from the model's static `$transKey`, or from a convention key derived from the controller name.

> **Composition note:** `HasDeleteMethods`, `HasToggleActiveMethods` and `HasFileActionsMethods` all declare `protected string $model`. That visibility is deliberate so the three traits can be used together on one controller.

#### `HasPinMethods`

Adds `pin()` — a per-user toggle. It calls `$model->pinUsers()->toggle([auth()->id() => $pivotData])` (optional `$pinPivotData` property on the model supplies pivot columns) and answers with `api.global.pinned` / `api.global.unpinned`.

### 9.2 Model behavior traits

#### `AdvancedFilter`

The most important filter trait. It powers the generic `advanced` request parameter:

```
advanced[0][key]=is_active&advanced[0][value][]=1
advanced[1][key]=created_by&advanced[1][value][]=7
```

The consuming class supplies two properties:

```php
protected array $filter    = ['advanced' => [...]];   // usually from the request
protected array $relations = ['many' => [ /* relation map */ ]];
```

Resolution rules:
- **Allowed keys** = relation-map keys + the real column list of the model's table. Anything else is **silently skipped** — arbitrary input can never produce SQL errors.
- A relation entry may declare `relation` (default: the key), `column` (default: `id`), `morph` and `morph_types`.
- An optional `$resolvers[$key] = ['enum' => …, 'method' => …]` mapping transforms values before they hit the query (e.g. label → stored value).
- Plain keys become `whereIn($key, $value)`; relation keys become `whereHas(...)`, with `whereHasMorph()` when `morph` + `morph_types` are present.

```php
'many' => [
    'locations'       => [],                                    // relation = key, column = id
    'actors'          => ['column' => 'actor_id'],
    'detection_status'=> ['relation' => 'detections.logs', 'column' => 'detection_status_id'],
    'detection_types' => [
        'relation'    => 'secondaryDetectionReferences',
        'morph'       => 'referenceable',
        'morph_types' => [EventDetection::class],
        'column'      => 'detection_type_id',
    ],
],
```

#### `HasDynamicScopes`

Applies request-driven scopes: `?scopes[]=active&values[active]=1`. It only calls a scope when `scope{Name}` actually exists on the model, and passes `values[...]` as the argument when present (an optional `$scopeMap` remaps a scope name to a different value key). Unknown scopes are ignored.

#### `CreatedByObserver`

On `creating`, sets `created_by` (or the column named by a static `$createdByColumn`) to `auth()->id()` when a user is authenticated. This is what makes ownership-based policies (`view-own-*`) work.

#### `HasDeletedBy`

Adds `deleted_by` to `$fillable`, stamps it with `Auth::id()` on `deleting`, and clears it on `restoring` — both with `saveQuietly()` so no extra events fire.

#### `LogsActivityOptions`

Wraps Spatie's `LogsActivity` with sane defaults: log all attributes, only dirty ones, log name = class basename, skip empty changes. A model may declare `$logExceptAttributes` to exclude fields. `User` overrides `getActivitylogOptions()` outright to log only its fillable list.

#### `ApplyNotification`

One method — `sendNotification(array $data, ?array $types = ['notify', 'realtime'])` — delegating to `NotificationService::resolve()`. Used on `User`.

#### `HasOrder`

`changeOrder(string $orderField, string $stepField, $request)` reorders a record inside a group, wrapped in a DB transaction: siblings between the old and new position are incremented or decremented, then the moved record is updated.

#### `LdapOperations`

`getLdapUsers($request)` queries the directory (OpenLDAP or Active Directory depending on `config('ldap.local')`), optionally filtering by `cn`/`mail`, drops entries with no email, and maps each to `{ name, email, phone, phone_code, uid, guid, ldap_name }` sorted by name. Returns `[]` when `config('ldap.active')` is false.

#### `QuotesSortLiterals`

`quoteSortLiteral(string $value)` quotes a literal through the model's own PDO connection. Needed because `sortableExpressions()` are handed to `orderByRaw` without bindings, and escaping differs between MySQL (backslash is an escape character) and SQLite.

---

## 10. Query Scopes

Reusable scope traits live in `app/Scopes/{Feature}/`.

### `UserScopes`

| Scope | Behavior |
|---|---|
| `related()` | **The ownership gate.** Unless the user has `view-all-user`, restricts to rows they created. |
| `excludeLoggedInUser()` | Hides the current user from a listing. |
| `excludeRoot()` | Excludes users holding the `root` role. |
| `withRole(?string $role)` | Filters by role name when one is given. |

### `RoleScopes`

`related()` combines the same `view-all-role` ownership check with `excludeRoot()` and `excludeLoggedInRole()` — so an admin can never see or edit the root role, nor the roles they themselves hold.

> `Model::related()` is the first thing in nearly every `index()`. Adding a new listing without it is the standard way to accidentally leak other admins' records.

---

## 11. Filters & The Pipeline Pattern

Listings are built with `Illuminate\Pipeline\Pipeline`. Each filter is a small class with `handle($request, Closure $next)` that calls `$next($request)` **first** and then mutates the resulting builder.

```php
$query = app(Pipeline::class)
    ->send(User::with('roles')->related())
    ->through([UserFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
    ->thenReturn();

return successResponse(wrapPaginate($query, UserResource::class));
```

### Global filters

| Filter | Request params | Behavior |
|---|---|---|
| `SearchFilter` | `search` | `LOWER(name) LIKE LOWER(%term%)` **or** a prefix match on `id`. |
| `NameFilter` / `EmailFilter` / `PhoneFilter` | `search` | Single-column `LIKE`. |
| `JsonNameFilter` / `JsonDisplayNameFilter` | `search` | JSON translatable search across every supported locale via `QueryHelper::applyJsonSearch`. |
| `ActiveFilter` | `is_active` | Exact boolean match when the key is present. |
| `TrashedFilter` | `is_trashed` | Switches to `onlyTrashed()`. |
| `DateFilter` | `start`, `end` | `created_at` range. |
| `OrderColumnFilter` | — | Orders by an `order` column when the table has one. **Place last** so it wins. |
| `OrderByFilter` | `sort_column`, `sort_direction` | See below. |

### `OrderByFilter` — the sorting engine

The most sophisticated filter in the codebase. Given `sort_column`, it resolves, in order:

1. **Computed expressions** — if the model defines `sortableExpressions(): array`, a matching key sorts by that raw SQL. This lets a listing sort by a value the resource *derives* (e.g. `remaining_days`) rather than reads.
2. **Relation shorthand** — a key that is neither a column nor dotted, but names a single relation (`creator`), is expanded to `creator.name`.
3. **Dotted relation columns** (`creator.name`) — sorted through a correlated subquery. Only `BelongsTo`, `HasOne` and `HasOneThrough` qualify; relations are recognized by their **declared return type** via reflection, so a crafted `sort_column` can never reach an unrelated method. `MorphTo` is rejected. Self-referencing relations read the related table under a `_sort` alias so the correlation is against the outer row.
4. **Enum label ordering** — string-backed enum columns are ordered by their **translated label** through a generated `CASE` expression, not by the stored value. Sources: an explicit `sortableEnums()` map on the model, or automatic detection from casts. Int-backed enums keep their deliberate numeric order.
5. **Displayed-column prefixes** — `translation_name` → `name`, `display_type` → `type`.
6. **Translatable columns** — sorted as `name->{locale}`, since ordering by the raw JSON document would always let `ar` decide the order.
7. **Plain columns**, verified with `Schema::hasColumn()`.

JSON-path sorts are wrapped in `LOWER(...)` because MySQL extracts JSON as `utf8mb4_bin`, which sorts byte-by-byte (all lowercase after all uppercase). Anything unresolvable falls back to `id`; every failure is logged, never thrown. Direction defaults to `desc`.

### Feature filters

| Filter | Behavior |
|---|---|
| `UserFilter` | `search` across `name`/`email`/`phone`, plus `AdvancedFilter` with `created_by` mapped to the `creator` relation. |
| `NotificationFilter` | `group` → `whereIn('data->group', …)`. |
| `ActivityLogFilter` | `search` (description), `model`, `user_id`, `operation`, `date_from`, `date_to`. |
| `GroupFilter` / `KeyFilter` | Settings listing filters. |

---

## 12. Services

Controllers stay thin; anything transactional or reusable lives in a service.

### Auth services

#### `LoginService`

`attempt(array $data): ['user' => Model, 'token' => string]`

1. **LDAP or default** based on `config('project.ldap.active')`.
2. `attemptDefaultLogin()` — email lookup + `Hash::check`, else `InvalidEmailAndPasswordCombinationException` (406).
3. `attemptLdapLogin()` — tries `uid`, `cn`, `samaccountname`, `userprincipalname`, `mail`; **falls back to default login when no directory entry matches**; binds with the supplied password; `findOrCreateUserFromLdap()` upserts the local user by `uid` + `email` and assigns `config('project.auth.default_role')` on first creation.
4. Inactive user → `InActiveUserException`.
5. When `shouldVerifyOtp()` is true, verifies the login OTP.
6. `setLastLogin()`, then `createUserToken()` — which stores the rich device `meta` on the personal access token.

#### `OTPService`

A complete one-time-password engine storing state in `users.otp_data` keyed by type (`login`, `reset_password`, `verify_email`):

```json
{ "login": { "otp": "…", "sent_at": "…", "expires_at": "…", "attempts": {"<ip>": 2}, "locked_until": {"<ip>": "…"} } }
```

| Method | Behavior |
|---|---|
| `send($request, $type)` | Enforces the resend delay, generates the code (`numeric`/`alpha`/`alphanumeric`, configurable length, or the fixed `otp.default`), stores expiry/attempt state, emails a type-specific template. |
| `verify($request, $type)` | Validates, then **clears** the OTP; marks `email_verified_at` for `verify_email`. |
| `check($request, $type)` | Validates **without** consuming — for multi-step flows like password reset. |

Validation checks lock status → expiry → value, **per IP address**. A wrong code increments `attempts[ip]`; hitting `max_attempts` sets `locked_until[ip]`. All failures throw `InvalidOtpException` with a translated message (`otp_locked_try_later`, `otp_expired`, `invalid_otp`, `otp_already_sent_wait`).

#### `ResetPasswordService`

Uses `OTPService::check()` (not `verify()`), sets the new password, then removes only the `reset_password` OTP entry.

#### `ThrottleService`

Thin wrapper over `RateLimiter`: `generateThrottleKey(email, ip)` → `"email|ip"`, plus `ensureIsNotRateLimited()`, `incrementRateLimit()`, `clearRateLimit()`. `LoginController` hits it before authenticating, increments on auth failures, and clears on success.

### Global services

| Service | Purpose |
|---|---|
| `SettingService` | Loads all settings into a nested array keyed by the dotted `group` path, cached forever per brand (`settings_{brand}`). `get($path, $lang, $default)`, `bool($path, $default)` (distinguishes explicit `false` from unset), `clearCache()`, `updateSettings(array)` (handles media uploads and `.env` sync for `is_env` rows). Backing store for the `setting()` / `settingBool()` helpers. |
| `NotificationService` | `resolve($user, $data, $types)` fans out across `notify` (database), `realtime` (broadcast), `email`, `sms`. **Each channel is wrapped in try/catch and logged** — a failing channel never breaks the request. |
| `QueryHelper` | `applyJsonSearch()` — case-insensitive `LIKE` (or exact) across every locale of a JSON column. `applyIdSearch()` — prefix-matches numeric search terms against the (alias-aware) `id` column, skipping non-numeric terms so MySQL doesn't silently cast to `0`. |
| `EncryptionService` | AES-256-CBC encrypt/decrypt with a random IV, keyed by `project.auth.encryption.key`. Used by `LoginResource` to encrypt roles/permissions before they leave the API. |
| `DiscoveryConfigResolver` | Invokable resolver bound into `ConfigLookupManager`. Recursively translates every `label` entry in `config/discovery.php` **at request time**, because config files load before the translator and before the locale middleware. |

### `UserService`

`store()` and `update()` both run in `DB::transaction()`, call `syncRelations()` (roles by id → name, plus direct permissions), and register `DB::afterCommit(fn () => $this->sendCredentials(...))` so notification side-effects never run inside the transaction. On update, credentials are only re-sent when `email` or `password` actually changed.

---

## 13. Global Helper Functions

Defined in `app/Helpers/App.php` (autoloaded via composer `files`). Every one is wrapped in `function_exists()`.

### Responses

| Function | Purpose |
|---|---|
| `successResponse($data = [], $msg = null, $code = 200)` | Standard success envelope. |
| `failResponse($msg = 'fail', $data = [], $code = 400)` | Standard failure envelope. |
| `abort403($condition = true)` | Aborts 403 with `api.no_required_permissions` when the condition holds. |
| `unKnownError($message = null)` | JSON or redirect depending on the request; includes the message only in debug. |

### Type checks

`isArrayIndex($v)` (list vs map) · `iSnake($v)` · `isBase64($v)` · `isRoot($user)` (has the `root` role).

### Resolvers

| Function | Purpose |
|---|---|
| `resolveTrans($key, $page = 'api', $lang = null, $snaked = true)` | Translate with a graceful fallback to the raw key; returns `'---'` for empty input. |
| `resolveBool($item)` | `0`/`1` → translated no/yes. |
| `resolvePhoto($image, $type = 'user')` | Storage URL, pass-through for absolute URLs, or a default avatar/blank image. |
| `resolveArray($v)` | String → comma-split array. |
| `resolveModel(string $name, $module = null)` | Resolves `App\Models\X` or `Modules\{Module}\App\Models\X`. |
| `resolveClass(string $path)` | `app($path)` when the class exists. |
| `resolveEmptyLang(array $trans)` | Fills a missing `ar`/`en` from the other. |
| `resolveEmptyToNull($value)` | Recursively converts `""`, `"null"`, `[]` → `null`. Used by `BaseFormRequest`. |

### Query & response shaping

| Function | Purpose |
|---|---|
| `wrapPaginate(Builder $query, $resource = null, $meta = [])` | The standard listing wrapper. Uses `per_page` (default `project.pagination.per_page`); `per_page = -1` returns everything unpaginated. Paginated payloads gain a **`sorting`** key. |
| `fetchData(Builder $query, $pageSize, $resource, $meta, $pageName = 'page', ?int $page = null)` | Same, but with an explicit page size, a custom `$pageName` (so several paginators can coexist in one response, e.g. one per kanban column), and a forced page number. |
| `resourceKeys($resource, ?Model $model)` | The keys an API resource exposes — accepts an instance, a collection (first item defines the shape), or a class name + model. |
| `resourceSorting($resource, ?string $module, ?Model $model)` | Maps each resource key to the `sort_column` value the frontend must submit, from `config('discovery.sorting.{module}')`. Non-sortable keys map to `null`. |

### Model utilities

`getModelKey($class, $case = 'snake')` · `detectModelPath($type)` · `allModelsNames()` · `allAttributesFillableModels()` · `findSoftDeletedModel($class, $conditions)` (JSON-aware trashed lookup) · `toggleBooleanAttribute($model, $attr)` · `canDelete($object)` (throws 404 when `can_delete` is false) · `getCurrentGuard()`.

### Message packing (the delimiter protocol)

Notification bodies are stored as a **packed string** so a message can be re-translated per recipient at render time:

```
create_admin_data_msg|name=John|email=john@example.com|enum_status=App\Enum\StatusEnum@Active
```

| Function | Purpose |
|---|---|
| `buildDelimiterMessage(string $key, array $params)` | Packs the string. Values may be scalars, `DelimiterParamValue::json()` (per-locale JSON) or `DelimiterParamValue::enum()` (`FQN@Case`). |
| `transWithParams(?string $data, $page, array $params)` | Unpacks and translates. `enum_` keys resolve through the enum's `resolve()`; JSON values pick the current locale (falling back to `en`). |
| `emailTrans(?string $data, array $params)` | `transWithParams` scoped to `notifications.emails`, with `platform_name` injected. |
| `parseKeyValueString($data, $page = 'api')` | Legacy/simple variant, also handling `['id' => …, 'parameters' => …]` and per-locale arrays. |

`App\Helpers\DelimiterParamValue` is the small value object with the `plain()`, `json()` and `enum()` constructors.

### Settings & branding

`setting($path, $lang = null, $default = null)` · `settingBool($path, $default = true)` · `notificationChannelEnabled($channel)` (maps a channel name to its master switch under `notifications.*`, defaulting to enabled) · `brandSettings(?$lang)` (the full brand array: name, logos, theme colors, fonts, mail theme, OTP mail styling, contact, social) · `brandName()`.

### Misc

`updateDotEnv(array)` · `logError($e)` · `when($condition, callable)` (truthy for non-empty arrays/collections/strings and `true`) · `safeExecute($callback, $return = true)` (re-throws locally, logs and returns otherwise) · `rootUsers()` · `utf8StrRev()` · `imageExtensions()` / `vImage($ext)` · `checkFromPath($path, $rules)` (validates an already-stored file against upload rules) · `rulesBasedOnFlag($rules, $flag)` · `shouldVerifyOtp()` · `encryptCode(array)`.

---

## 14. Validation: Form Requests & Custom Rules

Form Requests are grouped by feature under `app/Http/Requests/{Feature}/` and extend `BaseFormRequest`. Notable ones: `LoginRequest`, `SendOtpRequest`, `VerifyOtpRequest`, `ResetPasswordRequest`, `UserRequest`, `RoleRequest`, `PermissionRequest`, `CountryRequest`, `SettingRequest`, `ReportRequest`, `ExportRequest`, `ChunkFileRequest`, `PageRequest`, `ModelBatchRequest`, `DeleteAllRequest`, `OrderRequest`, the three `Help*Request` classes.

`PageRequest` is the shared listing request — it validates just `page` and `per_page`.

`UserRequest` is the reference implementation: it overrides `prepareForValidation()` to unpack a nested `phone` object into `phone` + `phone_code_id` and to wrap a scalar `roles` value, and composes three custom rules (`ValidLength`, `UniqueCheck`, `StrongPassword`) alongside standard ones.

### Custom rules (`app/Rules`)

| Rule | Behavior |
|---|---|
| `UniqueCheck` | The richest rule. Live duplicate → 422. **Soft-deleted duplicate → 433** with the trashed record attached (via `ModelAlreadyExistsException`), so the client can offer "restore instead of create". Translatable values collide as soon as **one** language matches. Supports `$wheres` scoping, a custom `$messageKey`, and a custom `$attributeKey` for the human label. |
| `ModelExists` | Existence check against a model + column, automatically excluding soft-deleted rows, with optional extra `where` conditions. |
| `TranslatableRequired` | Per-locale validation driven by `config('lang.languages_validation')` — required locales vs optional ones, with `unique` support against the translatable JSON column. |
| `TranslatableNullable` | The nullable counterpart. |
| `StrongPassword` | Rejects dictionary words and any word derived from the user's own name parts (longer than 2 chars). |
| `CheckSamePassword` | New password must differ from the current one. |
| `ValidLength` | Exact length driven by another model's column — used so a phone number matches its country's `phone_length`. |
| `NotEmptyFile` | Rejects zero-byte uploads (Laravel's `min` measures in KB and cannot express "at least one byte"). |
| `TotalFileSize` | Caps the **combined** size of new uploads plus existing attachments. |

---

## 15. Enums

Every enum uses `HasanHawary\LookupManager\Trait\EnumMethods`, which supplies `getList()` (value/label pairs), `resolve($value)` (translated label) and the lookup-endpoint integration. Enum **keys use TitleCase**.

| Enum | Backing | Cases |
|---|---|---|
| `ActiveTypeEnum` | int | `Active = 1`, `InActive = 0` |
| `NotificationGroupEnum` | int | `Global = 1` |
| `OtpTypeEnum` | string | `login`, `reset_password`, `verify_email` |
| `ReportChartTypeEnum` | string | `high_chart` (+ `default()`) |
| `ReportPageTypeEnum` | string | `user` |
| `SettingTypeEnum` | string | `text`, `textarea`, `imageUploader`, `file`, `checkBox`, `radio`, `switchbox` |
| `UserGenderEnum` | string | male / female |

The `Modules/Notification` module adds its own set: `NotificationChannelEnum`, `NotificationEventTypesEnum`, `SystemEventSlugEnum`, `SystemEventModuleEnum`, `VariableTypeEnum`, `ScheduleEventTypeEnum`, `ScheduleEventSourceEnum`, and three `ReminderSetting*` enums.

> **Sorting interaction:** string-backed enums are automatically sorted by translated label (§11); int-backed enums keep their numeric order. That distinction is deliberate.

---

## 16. API Resources

Grouped under `app/Http/Resources/{Feature}/`.

- **`UserResource`** — nests phone as `{ phone, phone_code, phone_code_id }`, exposes both the raw `gender` and `display_gender`, and uses `whenLoaded(..., fallback)` for `roles`, `creator` and `settings` so an unloaded relation still yields a predictable shape.
- **`BasicResource`** / **`BasicUserResource`** — the minimal `{ id, name, description }` projection used for nested relations.
- **`LoginResource`** — returns the token plus the user, with `roles` and `permissions` **AES-encrypted** when `project.auth.encryption.outgoing.*` says so.
- **`SessionResource`** — renders the `meta` column of each personal access token as a readable device/session entry.
- **`SettingGroupResource::organizeNested()`** — a static builder (not a normal resource) that turns a flat settings collection into `group → tabs → items`, translating labels via `settings_trans.*`.
- **`ActivityLogResource`**, **`NotificationResource`**, **`RoleResource`**, **`PermissionResource`**, **`CountryResource`**, **`UserSettingResource`** — conventional projections.

---

## 17. Authorization: Policies, Roles & Permissions

### Permission naming

`{operation}-{kebab-model}` — generated by the permission-manager package from each model's `$inPermission`, `$basicOperations`, `$specialOperations`, plus `config('roles.php').additional_operations`. Examples: `create-user`, `view-all-role`, `toggle-active-country`, `force-delete-user`, `read-log`, `update-setting`.

### Three enforcement mechanisms

1. **Policies** — `Gate::authorize('view', User::class)` in controllers.
2. **Middleware** — `Spatie\Permission\Middleware\PermissionMiddleware` declared through the controller's static `middleware()` method (Laravel 12+ `HasMiddleware` style):
   ```php
   public static function middleware(): array
   {
       return [new Middleware(PermissionMiddleware::using('update-setting'), only: ['update'])];
   }
   ```
3. **Query scopes** — `related()` restricts a listing to owned rows unless the user holds `view-all-*`.

### `UserPolicy` / `RolePolicy`

Both follow the same shape:

- `ownsOrAll($user, $model)` — passes when there is no model, the user has `view-all-*`, or `created_by === $user->id`.
- `canAny()` / `canAct()` — accepts either the `view-all-*` or `view-own-*` permission, then applies the ownership check.
- **Protection rules.** `UserPolicy::isProtectedUser()` blocks any action against a root user **or against yourself** (update / delete / force-delete / toggle-active). `RolePolicy::isProtectedRole()` blocks every mutation of the `root` role.

`isRoot($user)` bypasses `HasDeleteMethods`' policy check entirely — root can always delete.

---

## 18. Authentication & Sessions

### Login flow

```
POST /api/login  { email, password, otp?, meta? }
  ThrottleService.ensureIsNotRateLimited("email|ip", max_login_attempts)
  LoginService.attempt()
      ├─ LDAP (if project.ldap.active) → bind → findOrCreateUserFromLdap
      │    └─ no directory entry → falls back to default login
      └─ default → email + Hash::check
  is_active guard
  shouldVerifyOtp() → OTPService.verify(type = login)
  setLastLogin()
  createUserToken() → PersonalAccessToken with device meta
  → LoginResource { token, user{…, roles*, permissions*} }   (* encrypted when configured)
```

On any auth failure the throttle counter is incremented with `lockout_time` decay; on success it is cleared.

### OTP endpoints

`POST /api/send-otp`, `/check-otp`, `/verify-otp` — all take a `type` (`login`, `reset_password`, `verify_email`). `check` validates without consuming; `verify` consumes.

### Password reset

`POST /api/reset-password` — `ResetPasswordService` checks the `reset_password` OTP, sets the password, and clears only that OTP entry.

### Sessions

`GET /api/me` returns `{ sessions, user }`, where `sessions` lists every active personal access token with its device metadata.

`POST /api/logout` revokes one of three scopes, chosen by the request body:

| Body | Effect |
|---|---|
| `all_devices=true` | Deletes every token the user holds. |
| `token_id=<id>` | Deletes that one session (404-style failure when it does not exist). |
| *(empty)* | Deletes only the token the request authenticated with. |

---

## 19. Exceptions & Error Handling

### Domain exceptions (`app/Exceptions`)

| Exception | Default code |
|---|---|
| `AccountNotFoundException` | 403 |
| `EmailVerifiedException` | 401 |
| `InActiveUserException` | 403 (`api.account_not_active`) |
| `InvalidEmailAndPasswordCombinationException` | 401 |
| `InvalidOtpException` | 406 |
| `InvalidPasswordResetTokenException` | 403 |
| `ModelAlreadyExistsException` | **433** — carries `getData()` with the conflicting (usually trashed) record |

### Render handlers (`bootstrap/app.php`)

Every handler checks `acceptsJson()` / `is('api/*')` before responding, so web routes keep normal behavior.

| Exception | Response |
|---|---|
| `NotFoundHttpException` | `api.record_not_found` at the original status |
| `AuthenticationException` | `auth.unauthenticated`, 401 |
| `UnauthorizedException` (Spatie) | `api.unauthorized`, 403 |
| `AuthorizationException` / `AccessDeniedHttpException` | 403 — **keeps the refusal's own message** when it has one; falls back to `api.unauthorized` for a bare `false` or Laravel's English default |
| `PermissionDoesNotExist` | `api.permission_not_found`, 403 |
| `MethodNotAllowedHttpException` | `api.action_not_available`, 405 |
| `BindException` (LDAP) | code 49 → `api.invalid_credentials` (401); anything else keeps its message (infrastructure problem) |
| `ValidationException` | `{ message, errors }` — strips Laravel's `(and N more errors)` suffix, since the bag already carries them |
| The domain exceptions above | Their own message + code |

---

## 20. Notifications, Events, Jobs & Mail

### The fan-out

```php
$user->sendNotification([
    'title'       => 'create_admin_data_title',
    'msg'         => buildDelimiterMessage('create_admin_data_msg', $params),
    'target_id'   => $user->id,
    'target_type' => 'users',
    'group'       => NotificationGroupEnum::Global->value,
], ['email', 'realtime', 'notify']);
```

`NotificationService::resolve()` dispatches to:

| Type | Implementation |
|---|---|
| `notify` | `UserNotify` notification on the `database` channel → the `notifications` table (`target_id`, `target_type`, `group`, `title`, `message`). |
| `realtime` | `NotificationEvent` broadcast on `notification.user.{id}` — **only when `project.realtime.enabled`**. |
| `email` | `BasicMail` (queued) rendering `emails.basic_mail` with the full `brandSettings()` array. |
| `sms` | `SendSmsJob` — queued HTTP POST to the configured SMS gateway, with retry/backoff and a 10s timeout. |

Every channel is individually try/caught and logged through `logError()`.

`notificationChannelEnabled($channel)` lets an admin globally disable `mail`, `sms`, `push` or `realtime` from the Notifications settings sub-module.

### Jobs

- **`SendEmailJob`** — bulk fan-out; iterates a user collection through `NotificationService::resolve()`.
- **`SendSmsJob`** — the gateway call, gated by `services.sms.enable`.

### Mail

- **`BasicMail`** — `implements ShouldQueue`; subject from `transWithParams($data['title'])`; view `emails.basic_mail` with `data` + `brand`.
- **`BasicMailWithoutQueue`** — the identical mailable for synchronous sends (e.g. the "send test mail" settings action).

### Broadcasting

`App\Events\NotificationEvent implements ShouldBroadcast` broadcasts on the public channel `notification.user.{user_id}` with `{ target_id, target_type, url, title, message, created_at }`, translating both title and message through `notifications.realtime`. Reverb is the WebSocket server.

---

## 21. Settings System

A database-backed, brand-aware, template-driven configuration system.

- **Shape.** Each `settings` row is `{ key, value, group, type, label, placeholder, is_multi_lang, is_env }`. `group` is a dotted path (`theme.colors`, `mail_templates.otp`) which becomes the nesting.
- **Reading.** `SettingService::all()` builds the nested tree once and caches it **forever** under `settings_{brand}`. `setting('theme.colors.primary_color')` and `settingBool('notifications.mail_support')` read from it. Use `settingBool()` for switchboxes — plain `get()` cannot distinguish an explicit `false` from an unset value.
- **Writing.** `PUT /api/settings` (guarded by `update-setting`) → `updateSettings()`: media types are uploaded through `Media::replace()->upload(..., 'settings')`, `is_env` rows are written back to `.env` via `updateDotEnv()`, then the cache is cleared.
- **Exposure.** `GET /api/settings` returns only `scopePublic()` rows (never `is_env` ones) for authenticated users, nested into `group → tabs → items` by `SettingGroupResource::organizeNested()`.
- **Branding.** `brandSettings()` assembles a single array — name, website, four logo variants, theme colors, fonts, mail theme and OTP mail styling, contact details and social links — which is what mail templates render against.
- **Testing credentials.** `POST /api/send-test-mail` (`TestCredentialsController`) verifies configured mail credentials.

---

## 22. Activity Logging

Powered by `spatie/laravel-activitylog` through the `LogsActivityOptions` trait (§9.2).

- **Reading:** `GET /api/activity-logs` and `GET /api/activity-logs/{activity}`, both behind the `read-log` permission, eager-loading `causer` and `subject`.
- **Filtering:** `ActivityLogFilter` supports `search` (description), `model` (resolved through `detectModelPath()`), `user_id` (causer), `operation`, `date_from`, `date_to`. `OrderByFilter` handles sorting.
- **Opting a model in:** `use LogsActivityOptions;` and, optionally, `protected array $logExceptAttributes = ['password'];`.

---

## 23. Reports & Exports

### Report Builder

- Report classes live in `App\Tools\Report` and extend `HasanHawary\ReportBuilder\BaseReport`.
- `config/report.php` declares each **page**, and each component within it (`type`: `card` / `table` / `spline` / …, plus a responsive `size` grid).
- `GET /api/report?page=user&start=…&end=…&prefer_chart=…` → `ReportController` builds the filter array (defaulting `page` to `user` and `prefer_chart` to `ReportChartTypeEnum::default()`) and hands it to `ReportBuilder`.
- A report class implements one method per component: `getCards()`, `getRegisteredUsersByDate()`, `getUserByGender()` — mapped from the config key by convention. Results go through `cardResponse()` / `chartResponse()`.
- `BaseReport` supplies `applyDateFilter()`, `applyAdvancedFilters()`, `checkSoftDelete()`, `guessDateFormat()`.
- `component_errors => 'throw'` surfaces a broken component instead of silently dropping it. Consider `'ignore'` in production.

> ⚠️ `UserReport` uses MySQL's `DATE_FORMAT()`. It cannot run on the SQLite test connection — see §30.

### Export Builder

- Export classes live in `App\Tools\Export` and extend `HasanHawary\ExportBuilder\BaseExport`, declaring the model, the column list with types, relation projections, and the advanced-filter relation map:

```php
'columns'   => ['id' => 'int', 'name' => 'text', 'gender' => UserGenderEnum::class, 'is_active' => 'boolean', …],
'relations' => [
    'one'  => ['created_by' => ['creator' => ['name' => 'text', 'id' => 'int']]],
    'many' => ['count' => [], 'list' => [], 'concat' => ['roles' => ['display_name' => 'text']]],
],
```

- Resolution is by page name: `page=user` → `App\Tools\Export\UserExport`.
- The package owns `/api/export`, `/api/export-direct` and `/api/export-log` (enabled in `config/export.php`), streaming with `lazyById()` at `chunk_size` rows, storing under the `public` disk in `exports/`, and resolving headings from `lang/{locale}/export.php`.
- Export permissions are **disabled by default** — a fresh project has none seeded. Enable and map abilities per page once `config/roles.php` declares them.

---

## 24. Lookup / Help Endpoints & Discovery Config

Three endpoints let the frontend build forms and filters without hardcoding anything:

| Endpoint | Returns |
|---|---|
| `GET /api/help-models` | Rows from a whitelisted model (id + display name), with optional scopes/values. |
| `GET /api/help-enums` | An enum's cases as value/label pairs (`method` selects e.g. `getList`). |
| `GET /api/help-configs` | Whitelisted config values — chiefly `discovery.filters` and `discovery.sorting`. |

`HelpController` delegates to the `Lookup` facade and wraps the result in `successResponse()` (the package's own routes are disabled in `config/lookup.php` so responses share the app envelope).

### `config/discovery.php`

Two sections:

**`filters`** — the filter form each module exposes, already frontend-ready (`type`, translated `label`, responsive `size`):

- A **`date_range`** field exposes one key (`created_at`); the frontend derives `created_at_from` / `created_at_to`. Either bound may be sent alone.
- A **`select`** field declares where its options come from: `reference_type` (`help-models` / `help-enums` / `help-configs`), `name`, `module`, and `actions` (extra lookup params, e.g. `['scopes' => ['active']]`). A scoped `help-models` lookup may add `values`, positionally matched to `scopes`.
- Labels are stored as **translation keys**, not translated strings, because config loads before the translator. `DiscoveryConfigResolver` translates them per request.

**`sorting`** — the columns each module may be sorted by. An entry is either a plain column or `displayed => actual` (e.g. `creator => creator.name`). `resourceSorting()` uses this map to tell the frontend, for each resource key, exactly what to send as `sort_column`.

### Access control

`config/lookup.php` gates which models and columns are reachable. The lookup endpoints are available to **any authenticated user regardless of role**, so `blocked_extra_columns` (credential columns) must never be relaxed.

---

## 25. Media & Chunked Uploads

The `Media` facade (media-manager) is the single entry point for files:

```php
Media::from($file)->to('users')->store();      // store, returns the path
Media::replace($oldPath)->upload($file, 'settings');
Media::delete($path);
Media::url($path);                              // public/signed URL
```

Models integrate through accessors/mutators — see `Country::setFlagAttribute()` / `Country::flag()` and `User::avatar()`.

**Chunked uploads:** `POST /api/chunk-file` (`ChunkFileController` + `ChunkResolver`) accepts a chunk plus `path` and `is_final`, and returns the assembled path once the final chunk lands. Use it for large files instead of a single multipart request.

Related validation rules: `NotEmptyFile`, `TotalFileSize`, and the `checkFromPath()` helper for validating already-stored files.

---

## 26. Localization & Translatable Content

- **Locales:** `en` and `ar`. `LanguageMiddleware` reads `Accept-Language` and **defaults to `ar`**.
- **Files:** `lang/en/*` and `lang/ar/*`, both containing `api.php`, `auth.php`, `enums.php`, `export.php`, `lookup.php`, `notifications.php`, `pagination.php`, `passwords.php`, `report.php`, `validation.php`. Modules ship their own `lang/` directories.
  - `api.php` is the general message file and also holds nested groups such as `settings_trans` (settings group/tab labels), `global`, `filter` and `action_modules`.
  - `notifications.php` is split by delivery channel — `realtime`, `notify`, `sms`, `email` — so the same logical message has a per-channel wording.
  - Field labels live under `validation.attributes`.
- **Translatable columns** use `spatie/laravel-translatable`: declare `public array $translatable = ['name', 'nationality'];`. Values are stored as JSON (`{"en": "...", "ar": "..."}`).
- **Validating them:** `TranslatableRequired` / `TranslatableNullable`, driven by `config('lang.languages_validation')`, which says which locale is required and which is optional.
- **Searching them:** `QueryHelper::applyJsonSearch()` via `JsonNameFilter` / `JsonDisplayNameFilter`.
- **Sorting them:** `OrderByFilter` sorts by `column->{locale}` wrapped in `LOWER()` — never by the raw JSON document.
- **Message keys** in notifications use the delimiter protocol (§13) so the same stored message renders in each recipient's locale.

---

## 27. Feature Modules

Modules live in `Modules/{Name}` (nwidart), mirror the root `app/` layout inside `Modules/{Name}/app/`, and are toggled in `modules_statuses.json`. Both current modules are **enabled**. Being API-only, modules ship no frontend assets.

### `Modules/Form` — Dynamic Form Builder

Lets administrators define forms at runtime and collect submissions against them.

**Domain models:** `Form`, `FormStep`, `FormField`, `FormRelated`, `FormSubmission`, `FormSubmissionValue`.

**Service layer** (`Modules/Form/Tools/Form/`): `FormBuilder`, `FormService`, `FormSubmissionService`, `FormReferenceResolver`, behind the `Form` facade and three contracts.

**Core invariants** (from `.agents/skills/dynamic-form-development/SKILL.md`):
- A published form with submissions is **immutable history**. Editing it creates the **next version** and deactivates the prior one, rather than rewriting the schema stored answers point at.
- Form, steps, fields, related targets, submission metadata and values are synchronized in **one service-owned transaction**.
- Field/step ownership is verified before modification — a submitted field id must belong to the form/step being updated.
- Dynamic validation is composed from the **persisted** field schema; allowed input types and rule schemas live in module config (`Modules/Form/config/form_validations.php`). Client-supplied validation classes, regexes, model classes or column names are never executed.
- Option references (enums/models) resolve through `FormReferenceResolver` against a whitelist, batched — never queried in a loop.
- `source` and `submission` morph types must stay compatible with the project morph map; a raw class name from the request is never trusted.

**Routes:** `forms` (apiResource + `restore` / `delete` / `force-delete` / `change-status` / `{form}/fields` / `validation-rules`) and `form-submissions` (apiResource + `source` / `restore` / `delete` / `force-delete`).

### `Modules/Notification` — Notification Event Engine

A configurable engine turning business events into multi-channel, optionally scheduled notifications.

**Models:** `SystemEvent`, `NotificationEvent`, `NotificationTemplate`, `NotificationRecipient`, `NotificationReceiver`, `NotificationVerifiableDate`, `Variable`, `VariableAssignment`, `Channel`, `NotificationLog`, `RemindersSetting`, `ReminderLog`, `NotificationReminderDispatch`, `ScheduleEvent`, `ScheduleEventReceiver`, `SystemNotification`.

**Delivery layer** (`Modules/Notification/app/Tools/`):
- `NotificationManager` + the `Notification` facade.
- `Factory/NotificationChannelFactory` resolving `Channels/{Email,Sms,Push,Notification,Reminder,Calendar}Channel` (all extending `BaseChannel`).
- `Services/Notification/`: `SystemEventService`, `NotificationEventService`, `VariableResolver`, `ActiveRelationFilter`.
- `Factory/SmsFactory` + `Services/Sms/TwilioService`.

**Events & listeners:** `NotificationEvent`, `NotificationUserEvent`, `PushNotificationEvent`, `ScheduleNotificationEvent` → `SendNotificationListener`, `SendNotificationUserListener`, `SendScheduleNotificationListener`, `SyncScheduleEventsWithSourceListener`. Delivery runs through the queued `SendNotificationJob`; `SendNotificationReminder` is the scheduled console command.

**Core invariants** (from `.agents/skills/laravel-notification-event-development/SKILL.md`):
- Only genuine business moments get a `SystemEventSlugEnum` case, with stable snake_case values.
- The system event's module and `model_type` must match the exact model passed at dispatch — the delivery service rejects a mismatch.
- Events are dispatched from the domain service that owns the state change, never from a Resource or a validation layer; when inside a transaction, delivery must happen **after commit**.
- Variables must exist at the moment the event fires; each access key is validated against real attributes, casts, translatable fields, enums and active relations.
- Recipient resolution is permission- and relation-aware, deduplicated, and free of N+1 queries.
- Reminders preserve idempotency, overlap protection, channel narrowing and the dispatch ledger.
- Seeders are idempotent and additive — administrator-authored templates, links and recipients are never wiped.
- Bilingual (en/ar) event names and templates are required; placeholder Arabic copy is not a finished translation.

**Routes:** `system-events` (+ `receivers`, `variables`, `verifiable-dates`), `notification-events` (apiResource + `reminder-setting`), `notifications` (index/update), `schedule-events/reminder`, `schedule-events/calendar[/{month}]`.

---

## 28. API Endpoint Reference

All routes are prefixed `/api`. Headers: `Accept: application/json`, `Accept-Language: en|ar`, `Authorization: Bearer {token}`.

### Public

| Method | Path | Purpose |
|---|---|---|
| GET | `captcha` | Generate a captcha (token + code, cached 10 min). |
| POST | `captcha/verify` | Verify and consume it. |
| POST | `login` | Authenticate; returns token + user. |
| POST | `reset-password` | Reset via OTP. |
| POST | `send-otp` | Send an OTP of a given `type`. |
| POST | `check-otp` | Validate without consuming. |
| POST | `verify-otp` | Validate and consume. |

### Authenticated (`auth:sanctum`)

**Profile**

| Method | Path | Purpose |
|---|---|---|
| GET | `me` | Current user + active sessions. |
| POST | `update-profile` | Update own profile (incl. avatar). |
| POST | `update-setting` | Upsert per-user JSON preferences. |
| POST | `destroy-avatar` | Remove the avatar. |
| POST | `logout` | Revoke current / all / a specific session. |

**Users** (`users`)

| Method | Path |
|---|---|
| GET / POST | `users` |
| GET / PUT | `users/{user}` |
| DELETE | `users/delete` (soft, bulk via `ids`) |
| DELETE | `users/force-delete` |
| POST | `users/restore` |
| PUT | `users/toggle-active` |

**Roles & permissions**

| Method | Path |
|---|---|
| GET | `permissions` |
| GET / POST | `roles` |
| GET / PUT | `roles/{role}` |
| DELETE | `roles/delete` |

**Data entry** (`countries`) — same shape as `users`: apiResource plus `delete`, `force-delete`, `restore`, `toggle-active`.

**Global**

| Method | Path | Purpose |
|---|---|---|
| GET / PUT | `settings` | Read public settings / update (needs `update-setting`). |
| POST | `send-test-mail` | Verify mail credentials. |
| GET | `report` | Report Builder payload (`page`, `start`, `end`, `prefer_chart`). |
| GET | `activity-logs`, `activity-logs/{activity}` | Audit trail (needs `read-log`). |
| GET | `help-configs`, `help-models`, `help-enums` | Lookup endpoints. |
| GET / PUT | `notifications` | List (with unopened count) / mark `open` or `read`. |
| POST | `chunk-file` | Chunked upload. |
| — | `export`, `export-direct`, `export-log` | Registered by the Export Builder package. |

**Module routes** — see §27.

### Common list parameters

| Param | Meaning |
|---|---|
| `page`, `per_page` | Pagination; `per_page=-1` returns everything. |
| `search` | Filter-dependent search term. |
| `sort_column`, `sort_direction` | Sorting (see §11). |
| `is_active`, `is_trashed` | Boolean listing switches. |
| `advanced[i][key]`, `advanced[i][value][]` | Advanced filters (see `AdvancedFilter`). |
| `{key}_from`, `{key}_to` | Date-range bounds; either may stand alone. |
| `scopes[]`, `values[...]` | Dynamic scopes (`HasDynamicScopes`). |

---

## 29. Console Commands & Installation

### `php artisan app:install`

`App\Console\Commands\Setup` bootstraps a fresh checkout end to end:

```bash
php artisan app:install \
  --brand="My Project" \
  --db-host=localhost --db-port=3306 --db-database=my_project_db \
  --db-driver=mysql --db-username=root --db-password=secret \
  [--no-seed]
```

Steps: copy `.env.example` → `.env` (if absent) → write the DB/brand/`FILESYSTEM_DISK` variables via `updateDotEnv()` → `key:generate` → create the MySQL database (`utf8mb4_unicode_ci`) over PDO → `migrate:fresh` → `db:seed` (unless `--no-seed`) → set up modules when `nwidart/laravel-modules` is installed → `storage:link` → print sample credentials. **On any failure it drops the database it created** and rethrows.

> Database creation is MySQL-only. Omitting `--db-database` generates a random name from the app name.

### Everyday commands

```bash
php artisan test --compact                    # full suite
php artisan test --compact --filter=testName  # one test
vendor/bin/pint --dirty --format agent        # format changed PHP
php artisan route:list --except-vendor        # inspect routes
php artisan config:show project.auth          # inspect config
php artisan reverb:start                      # WebSocket server
php artisan queue:work                        # process queued mail/SMS/notifications
php artisan pail                              # tail logs
```

---

## 30. Testing

PHPUnit 12; feature tests dominate. Existing coverage:

```
tests/Feature/
├── ActivityLog/ActivityLogTest.php
├── Authorization/AuthorizationBoundaryTest.php
├── Global/CreatedByObserverTest.php
├── Global/DeleteMethodsTest.php
├── Global/DiscoveryConfigTest.php
├── Global/ExceptionResponseTest.php
├── Global/FileActionsTest.php
├── Global/GlobalTraitsTest.php
├── Global/ModulesSetupTest.php
├── Global/PackageEndpointsTest.php
├── Global/PinAndLdapTest.php
├── Global/QueryFiltersTest.php
├── Global/ValidationRulesTest.php
├── Help/HelpLookupTest.php
└── Notification/NotificationScopeTest.php
```

### The SQLite / MySQL split — read this before debugging a test

`phpunit.xml` pins `DB_CONNECTION=sqlite`, but **the application runs on MySQL**. PHPUnit does not override a variable already present in the environment, so you can run the same suite against MySQL:

```bash
DB_CONNECTION=mysql DB_DATABASE=<throwaway_db> php artisan test
```

Do that for anything touching raw SQL — `App\Tools\Report\UserReport` uses `DATE_FORMAT()`, and the JSON search helpers emit MySQL JSON functions, none of which SQLite can run. **Create a throwaway database; never point tests at the development one.**

Treat that split as an environment limitation, not a defect to engineer around: do not rewrite shared query helpers to suit the test driver.

### Conventions

- Use factories and their custom states rather than hand-building models.
- Most tests are feature tests: `php artisan make:test --phpunit {Name}`.
- Cover happy paths, failure paths and edge cases.
- Run the minimum set of tests with `--filter` while iterating; run the suite before finishing.
- Do not delete existing tests without approval.

### Other gotchas

- Stale files under `bootstrap/cache/` have crashed boot after a package was added — check that directory when a boot failure names a class that should not exist.
- A package upgrade can break a subclass with no syntax error (a base method changing visibility is the recurring case). After one, actually load the affected classes rather than only running `php -l`.

---

## 31. Recipes: How To Add Things

> Before any of these, read `AGENTS.md` and the skill its routing table points at. The registry is authoritative.

### Add a CRUD resource

1. **Migration + model.** Extend `BaseModel`. Add `SoftDeletes` if it needs a recycle bin, `HasTranslations` for translatable columns, `CreatedByObserver` for ownership, `LogsActivityOptions` for auditing. Declare `$inPermission`, `$basicOperations`, `$specialOperations` so permissions are generated.
2. **Ownership scope.** Add a `{Model}Scopes` trait in `app/Scopes/{Feature}/` with a `related()` scope if listings must be ownership-restricted.
3. **Filter.** Add `app/Filters/{Feature}/{Model}Filter.php`, using `AdvancedFilter` where the frontend needs `advanced`.
4. **Form Request.** Extend `BaseFormRequest`; reuse `UniqueCheck`, `ModelExists`, `TranslatableRequired`.
5. **Resource.** Project the JSON; use `whenLoaded(..., $fallback)` for relations.
6. **Controller.** Extend `BaseController`, call `parent::__construct()`, set `$this->model`, `use HasDeleteMethods, HasToggleActiveMethods;`. Build `index()` with a Pipeline + `wrapPaginate()`. Authorize with `Gate::authorize()` or `PermissionMiddleware`.
7. **Policy** (if ownership rules are non-trivial) and register it in `AppServiceProvider`.
8. **Routes.** Follow the established prefix block shape:
   ```php
   Route::prefix('things')->group(function () {
       Route::delete('force-delete', [ThingController::class, 'forceDelete']);
       Route::delete('delete',        [ThingController::class, 'destroy']);
       Route::post('restore',         [ThingController::class, 'restore']);
       Route::put('toggle-active',    [ThingController::class, 'toggleActive']);
       Route::apiResource('/', ThingController::class)->parameters(['' => 'thing'])->except(['destroy']);
   });
   ```
   The bulk routes must come **before** `apiResource`, or `delete` and `restore` get swallowed as `{thing}`.
9. **Discovery config.** Add the module's `filters` and `sorting` entries in `config/discovery.php` (labels as translation keys).
10. **Permissions.** Add the resource to `config/roles.php` where roles need it; re-seed.
11. **Translations.** Add `en` and `ar` keys.
12. **Tests**, then `vendor/bin/pint --dirty --format agent`.

### Block deletion when a record is referenced

```php
public function preventDeleteRelations(): array
{
    return ['contracts', 'causes' => 'not_allowed_to_delete_linked_cause'];
}
```

### Make a computed column sortable

```php
public function sortableExpressions(): array
{
    return ['remaining_days' => 'DATEDIFF(deadline, NOW())'];
}
```

Then add `remaining_days` to `config('discovery.sorting.{module}')`. Use `QuotesSortLiterals::quoteSortLiteral()` for any literal embedded in such an expression.

### Sort an enum column by its label

String-backed enums cast on the model are handled automatically. For a column deliberately kept as a raw string:

```php
public function sortableEnums(): array
{
    return ['status' => CauseStatusEnum::class];
}
```

### Add a setting

Insert a `settings` row (via a seeder) with `key`, `group` (dotted path), `type` (a `SettingTypeEnum` value), translatable `label`/`placeholder`, and `is_env` when it must be mirrored into `.env`. Read it with `setting('group.path.key')` — or `settingBool()` for a switchbox. Add the group and tab labels under `settings_trans` in `lang/{locale}/api.php`.

### Send a notification

```php
$user->sendNotification([
    'title'       => 'thing_created_title',
    'msg'         => buildDelimiterMessage('thing_created_msg', ['name' => $thing->name]),
    'target_id'   => $thing->id,
    'target_type' => 'things',
    'group'       => NotificationGroupEnum::Global->value,
], ['email', 'realtime', 'notify']);
```

Add the keys under the matching channel group in `lang/{locale}/notifications.php` (`realtime`, `notify`, `sms`, `email`). Dispatch from the service that owns the state change, and after commit when inside a transaction.

### Add a report page

1. Create `App\Tools\Report\{Page}Report extends BaseReport`.
2. Implement one method per component (`getCards()`, `getSomethingByDate()`, …) returning `cardResponse()` / `chartResponse()`.
3. Declare the page and its components in `config/report.php` under `pages`.
4. Call `GET /api/report?page={page}`.

### Add an export page

1. Create `App\Tools\Export\{Page}Export extends BaseExport` with `model`, `columns`, `relations`, `filter_relations`.
2. Add headings to `lang/{locale}/export.php`.
3. Call the package's export endpoints with `page={page}`.

---

## Quick Reference Card

```php
// Responses
successResponse($data, __('api.created_success'));
failResponse(__('api.record_not_found'), code: 404);

// Listing
$query = app(Pipeline::class)
    ->send(Model::query()->related())
    ->through([ModelFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
    ->thenReturn();
return successResponse(wrapPaginate($query, ModelResource::class));

// Authorization
Gate::authorize('view', Model::class);
abort403(! auth()->user()->can('do-thing'));
isRoot(auth()->user());

// Settings
setting('theme.colors.primary_color');
settingBool('notifications.mail_support');
brandSettings();

// Media
Media::from($file)->to('folder')->store();
Media::replace($old)->upload($file, 'folder');
Media::url($path);

// Notifications
$user->sendNotification($data, ['email', 'realtime', 'notify']);

// Translation
resolveTrans('some_key');
transWithParams($packedMessage, 'notifications.emails');
buildDelimiterMessage('key', ['name' => 'John']);
```
