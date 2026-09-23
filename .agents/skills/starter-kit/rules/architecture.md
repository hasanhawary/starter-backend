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
| Form Request | Validation and input normalization | Database writes, business mutations; authorization follows [security-auth.md](security-auth.md) |
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

## Feature Scaffolding And Modules

Inspect existing `app/` and `Modules/` files before creating a feature. Do not assume a module generator command exists merely because `Modules\` autoload is configured. Follow sibling namespaces, including exact casing.

Create only the required layers: model/migration, request/resource/controller/routes, applicable filters and authorization, a service for non-trivial writes, factories/seeders, translations, and focused tests. Use [API and CRUD patterns](api-controllers-routes.md) for the end-to-end contract and the topic files linked in `../SKILL.md` for each layer.

### Module Paths

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

## Conventions And Scope

Follow sibling code over generic Laravel advice; do not introduce a second architecture for the same concern. The topic rules own concrete implementation requirements, so consult them rather than duplicating their checklists here. The layer table describes responsibility: simple single-model CRUD can remain in the controller, and a service may accept an existing Form Request while consuming validated data only.

New reference-data features belong under `DataEntry` in their controller/request/resource/filter layers. Models remain in `app/Models` unless the surrounding module defines otherwise. Reuse shared traits, filters, helpers, and enums; do not invent repositories, DTOs, action wrappers, or API versions without a concrete need. Backend-only scope includes API consumer alignment, not frontend generation.
