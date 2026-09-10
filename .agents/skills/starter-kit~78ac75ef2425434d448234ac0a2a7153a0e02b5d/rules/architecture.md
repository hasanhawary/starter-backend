# Architecture

Use this rule for backend context detection, folder placement, naming, and layer boundaries.

## Backend Identity

- Laravel 13 API-only backend on PHP `^8.3|^8.4|^8.5`.
- Authentication uses Laravel Sanctum 4 with a custom `SanctumGuard` and optional LDAP/OTP flows.
- Authorization uses Spatie Permission, `PermissionMiddleware`, Gates, and Policies.
- Responses use `successResponse()`, `failResponse()`, and `wrapPaginate()`.
- Query filtering uses `Illuminate\Pipeline\Pipeline` with reusable filters.
- Business logic lives in services, not controllers.
- Reusable behavior lives in traits under `app/Trait/Global/`.
- Translatable JSON fields use `spatie/laravel-translatable` plus custom validation rules.
- Media, exports, reports, permissions, and lookups use installed `hasanhawary/*` packages.
- Backend-only skill: do not generate Vue, Pinia, Figma, Tailwind UI, or frontend pages.

## Operating Flow

1. Detect context from paths, routes, classes, models, and modules.
2. Inspect sibling code before writing.
3. Reuse existing controllers, models, requests, resources, filters, services, traits, rules, helpers, and enums.
4. Place files in the starter structure.
5. Generate the smallest correct implementation.
6. Apply security, performance, pattern, and API-contract checks.
7. Run focused verification when feasible.

## Core Folders

```text
app/
  Console/Commands/             Artisan commands
  Enum/Global/                  Shared backed enums
  Enum/{Domain}/                Domain enums
  Events/                       Broadcast events
  Exceptions/                   Custom exceptions
  Filters/Global/               Reusable Pipeline filters
  Filters/{Domain}/             Domain Pipeline filters
  Guards/                       Custom Sanctum guard
  Helpers/                      App.php and helper value objects
  Http/Controllers/API/{Domain}/ API controllers
  Http/Middleware/              HTTP middleware
  Http/Requests/{Domain}/       Form Requests
  Http/Resources/{Domain}/      API Resources
  Jobs/                         Queued jobs
  Mail/                         Mailables
  Models/                       Eloquent models
  Notifications/                Notifications
  Policies/{Domain}/            Policies
  Providers/                    Service providers
  Rules/                        Custom validation rules
  Scopes/{Domain}/              Scope traits
  Services/{Domain}/            Business services
  Tools/{Domain}/               Export/report tool definitions
  Trait/Global/                 Shared traits
Modules/{Name}/                 Modular features
routes/api.php                  Main API routes
routes/channels.php             Broadcast channels
routes/console.php              Scheduled commands/tasks
```

## Layer Responsibilities

| Layer | Responsibility | Not Allowed |
| --- | --- | --- |
| Controller | HTTP input, authorization, delegation, response helper | Complex business logic, inline validation, raw query filtering |
| Form Request | Validation and input normalization | Database writes, response formatting, project authorization checks |
| Service | Business logic, transactions, relation syncing, side effects | HTTP responses, `request()`, validation, authorization |
| Model | Fillable, casts, relations, attributes, traits, scopes | HTTP response formatting, validation |
| Resource | API transformation and conditional relation output | Queries, business logic |
| Policy | Resource authorization and ownership rules | Response formatting or data fetching |
| Filter | One query concern in a Pipeline | Multiple unrelated concerns or writes |

## Naming

- Controllers: `PascalCaseController`, under `app/Http/Controllers/API/{Domain}/`.
- Services: `PascalCaseService`, under `app/Services/{Domain}/`.
- Requests: `PascalCaseRequest`, under `app/Http/Requests/{Domain}/`.
- Resources: `PascalCaseResource`, under `app/Http/Resources/{Domain}/`.
- Models: singular `PascalCase`.
- Tables: plural `snake_case`.
- Routes and URL prefixes: plural `kebab-case` where needed.
- Policies: `PascalCasePolicy`, under `app/Policies/{Domain}/`.
- Filters: `PascalCaseFilter`, under `app/Filters/{Domain}/` or `app/Filters/Global/`.
- Scopes: `PascalCaseScopes`, under `app/Scopes/{Domain}/`.
- Enums: `PascalCaseEnum` backed enums.
- Traits: `Has{Behavior}` or descriptive `PascalCase`, under `app/Trait/Global/`.
- Helpers: `camelCase` functions in `app/Helpers/App.php`.
- Permission names: `{action}-{model}`, for example `create-product`, `view-all-product`.

## Non-Negotiable Rules

- Controllers extend `App\Http\Controllers\API\BaseController`.
- Form Requests extend `App\Http\Requests\BaseFormRequest`.
- Models extend `App\Models\BaseModel` unless they intentionally extend vendor/auth base classes.
- API resources extend `Illuminate\Http\Resources\Json\JsonResource`.
- Responses use `successResponse()` or `failResponse()`, never raw `response()->json()` for normal endpoints.
- Lists use `wrapPaginate($query, ResourceClass::class)`.
- Writes use `$request->validated()`, never `$request->all()`.
- Authorization follows sibling pattern: `PermissionMiddleware` for simple action permissions, `Gate::authorize()`/Policies for resource ownership rules.
- Services never return `JsonResponse`, call `response()`, call `request()`, validate input, or authorize users.
- Multi-step writes use `DB::transaction()`.
- Side effects inside transactions use `DB::afterCommit()`.
- List endpoints use Pipeline filters.
- User-facing messages use translations like `__('api.created_success')`.
- Models use explicit `$fillable`; never `$guarded = []`.
- Tests use PHPUnit, not Pest syntax.
