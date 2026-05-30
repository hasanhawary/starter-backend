---
name: architecture
description: Use when creating new controllers, services, models, or organizing code structure. Covers folder structure, layering philosophy, naming conventions, module system, and the overall service-oriented architecture of this Laravel API backend.
---

# Architecture

## Philosophy
Service-oriented modular API backend on Laravel 13. Strict layer separation: Controllers handle HTTP, Services handle business logic, Models handle data, Traits provide cross-cutting behavior.

## Rules
- All API controllers live under `app/Http/Controllers/API/`
- Controllers MUST extend `BaseController` and be grouped by domain subdirectory
- Complex business logic MUST be extracted to a Service class injected via constructor
- Simple CRUD reads (index, show) MAY stay in the controller
- Models MUST extend `BaseModel` (or Spatie base classes for Permission/Role)
- All global reusable behavior MUST use Traits in `app/Trait/Global/`
- Filters MUST use the Pipeline pattern via `Illuminate\Pipeline\Pipeline`
- Form Requests MUST extend `BaseFormRequest`
- Authorization MUST use either `Gate::authorize()` or `Policy` classes
- All responses MUST use `successResponse()` or `failResponse()` helper functions
- Routes are API-only; `web.php` is minimal (welcome page only)
- Module-specific code lives under `Modules/{Name}/` with its own MVC structure

## Naming Conventions
- Controllers: `PascalCaseController` in domain folders (e.g., `API/User/UserController.php`)
- Services: `PascalCaseService` grouped by domain (e.g., `Services/Auth/LoginService.php`)
- Models: `PascalCase` singular (e.g., `User.php`, `ExportFile.php`)
- Enums: `PascalCaseEnum` with `HasanHawary\LookupManager\Trait\EnumMethods`
- Traits: `PascalCase` in `Trait/Global/` (e.g., `HasDeleteMethods.php`)
- Filters: `PascalCaseFilter` in `Filters/{Domain}/` (e.g., `Filters/Global/ActiveFilter.php`)
- Scopes: `PascalCaseScopes` in `Scopes/{Domain}/` (e.g., `Scopes/User/UserScopes.php`)
- Exceptions: `PascalCaseException` in `app/Exceptions/`
- Helpers: `camelCase` global functions in `app/Helpers/App.php`
- Resources: `PascalCaseResource` in `Resources/{Domain}/`
- Requests: `PascalCaseRequest` in `Requests/{Domain}/`
- Policies: `PascalCasePolicy` in `Policies/{Domain}/`

## Folder Structure
```
app/
  Console/Commands/          # Artisan commands
  Enum/Global/               # Shared enums (OtpTypeEnum, NotificationGroupEnum, etc.)
  Enum/User/                 # Domain-specific enums (UserGenderEnum)
  Events/                    # Broadcast events (NotificationEvent)
  Exceptions/                # Custom exception classes
  Filters/Global/            # Reusable filters (ActiveFilter, OrderByFilter, TrashedFilter)
  Filters/{Domain}/          # Domain-specific filters
  Guards/                    # Custom auth guards (SanctumGuard)
  Helpers/                   # Helper classes and global functions
  Http/
    Controllers/API/{Domain}/ # API controllers grouped by domain
    Middleware/               # HTTP middleware (LanguageMiddleware)
    Requests/{Domain}/       # Form request validation classes
    Resources/{Domain}/      # Eloquent API resources
  Jobs/                      # Queued jobs (SendEmailJob, SendSmsJob)
  Mail/                      # Mailable classes (BasicMail, BasicMailWithoutQueue)
  Models/                    # Eloquent models
  Notifications/             # Notification classes (UserNotify)
  Policies/{Domain}/         # Authorization policies
  Providers/                 # Service providers
  Rules/                     # Custom validation rules
  Scopes/{Domain}/           # Query scopes
  Services/{Domain}/         # Business logic services
  Tools/{Domain}/            # Export/Report tool definitions
  Trait/Global/              # Reusable traits
Modules/{Name}/              # Modular features (Export)
  app/                       # Module controllers, models, services, etc.
  config/                    # Module config
  database/migrations/       # Module migrations
  lang/{locale}/             # Module translations
  routes/                    # Module routes
config/                      # Application config files
database/factories/          # Model factories
database/migrations/         # Database migrations
database/seeders/            # Database seeders (including brands/)
routes/                      # API, web, console, channels routes
```

## Best Practices
- Use `php artisan make:` for generating new files
- Inject services via constructor, never use `app()` or `resolve()` in controllers
- Use `DB::transaction()` for multi-step writes with `DB::afterCommit()` for notifications
- Use Pipeline pattern for all list/index queries with composable filters
- Define `$inPermission`, `$basicOperations`, and `$specialOperations` on models used by permission manager
- Use `__invoke()` for single-action controllers (LoginController, ResetPasswordController, ReportController)
- Group routes by domain with `Route::prefix()` and `Route::middleware()`
- Use `when()` helper for conditional execution instead of if statements

## Anti-Patterns
- Never put business logic directly in controllers (extract to services)
- Never use `request()->all()` — use `$request->validated()` from Form Requests
- Never hardcode authorization — always use Gates or Policies
- Never create models without factories and seeders
- Never use inline validation — always use Form Request classes
- Never put routes outside of `routes/` or `Modules/*/routes/`
- Never mix concerns: filters are for queries, middleware is for requests

## Real Examples
Controller with service delegation:
```php
// app/Http/Controllers/API/User/UserController.php
class UserController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct(private readonly UserService $userService)
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn (User $user) => Media::delete($user->avatar));
    }

    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);
        $user = $this->userService->store($request);
        return successResponse(new UserResource($user), __('api.created_success'));
    }
}
```

Module structure (Export):
```
Modules/Export/
  app/Http/Controllers/ExportJobController.php
  app/Services/ExportFileService.php
  app/Jobs/ExportToExcel.php
  app/Jobs/ExportToPdf.php
  app/Models/ExportFile.php
  app/Scopes/ExportFileScopes.php
  routes/api.php
  config/config.php
  lang/{ar,en}/
```

## AI Instructions
When building new features in this codebase:
1. Place controllers in `app/Http/Controllers/API/{Domain}/`
2. Create a service in `app/Services/{Domain}/` for any non-trivial logic
3. Create a Form Request in `app/Http/Requests/{Domain}/`
4. Create a Resource in `app/Http/Resources/{Domain}/`
5. Use Pipeline filters for list endpoints
6. Use `Gate::authorize()` or Policies for authorization
7. Return `successResponse()` or `failResponse()` — never raw JSON
8. For modular features, use `Modules/{Name}/` structure
9. Always follow the existing naming and folder conventions exactly
