---
title: Architecture
description: Understanding the Admin Dashboard Kit architecture and project structure
---

# Architecture Overview

The Admin Dashboard Kit is built on a **modular, service-oriented architecture** designed for scalability, maintainability, and rapid development.

## Project Structure

```
starter-Backend/
├── app/
│   ├── Console/              # Artisan commands
│   ├── Enum/                 # Application enums
│   ├── Events/               # Event classes
│   ├── Exceptions/           # Custom exceptions
│   ├── Filters/              # Query filters for advanced filtering
│   ├── Guards/               # Authentication guards (Sanctum)
│   ├── Helpers/              # Helper functions
│   ├── Http/
│   │   ├── Controllers/      # API controllers organized by domain
│   │   ├── Requests/         # Form request validation
│   │   └── Resources/        # API response transformers
│   ├── Jobs/                 # Background jobs (queued tasks)
│   ├── Mail/                 # Mail templates and classes
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # Notification classes
│   ├── Observers/            # Model observers (events)
│   ├── Policies/             # Authorization policies
│   ├── Providers/            # Service providers
│   ├── Rules/                # Custom validation rules
│   ├── Scopes/               # Eloquent scopes
│   ├── Services/             # Business logic services
│   ├── Tools/                # Utility classes
│   └── Trait/                # Reusable traits
├── bootstrap/                # Bootstrap application
├── config/                   # Configuration files
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── public/                   # Web root, public files
├── resources/                # Vue.js components, CSS
├── routes/                   # API and web routes
├── storage/                  # Application storage
├── tests/                    # Unit and feature tests
└── vendor/                   # Composer dependencies
```

## Layered Architecture

The application follows a **three-layer architecture**:

### 1. **HTTP Layer** (Controllers & Requests)

Handles incoming requests, validation, and response formatting.

```
Request → Validation (Requests) → Processing → Response (Resources)
```

**Key Components:**

- `Http/Controllers/` — Request handlers organized by domain
- `Http/Requests/` — Form request validation
- `Http/Resources/` — API response transformation

**Example Flow:**

```php
// routes/api.php
Route::post('users', [UserController::class, 'store']);

// Http/Controllers/UserController.php
public function store(UserRequest $request): JsonResponse
{
    $service = app(UserService::class);
    $user = $service->create($request->validated());
    
    return successResponse(
        new UserResource($user),
        __('api.created_success')
    );
}
```

### 2. **Business Logic Layer** (Services)

Contains core application logic, independent of HTTP.

**Key Components:**

- `Services/` — Business logic (Auth, Notifications, etc.) 
- `Models/` — Eloquent models with relationships
- `Observers/` — Model event listeners
- `Policies/` — Authorization logic
``

### 3. **Data Layer** (Models & Queries)

Manages database interactions through Eloquent ORM.

**Key Components:**

- `Models/` — Database models with relationships
- `Scopes/` — Reusable query scopes
- `Filters/` — Advanced filtering logic

## Key Patterns

### Service Locator Pattern

Services are injected via Laravel's service container:

```php
public function __construct(
    protected LoginService $loginService,
    protected ThrottleService $throttleService
) {}

public function login(LoginRequest $request): JsonResponse
{
    $user = $this->loginService
        ->setGuard('api')
        ->setModel(User::class)
        ->attempt($request->validated());
    
    return successResponse(new LoginResource($user));
}
```

### Query Pipeline Pattern

Complex queries use Laravel's Pipeline to chain filters:

```php
$query = app(Pipeline::class)
    ->send(User::with('roles'))
    ->through([
        UserFilter::class,
        ActiveFilter::class,
        TrashedFilter::class,
        OrderByFilter::class
    ])
    ->thenReturn();
```


### Authorization with Gates & Policies

```php
// In controller
Gate::authorize('view', User::class);
Gate::authorize('update', $user);

// In policy
public function update(User $authUser, User $targetUser): bool
{
    return $authUser->hasRole('admin') || $authUser->id === $targetUser->id;
}
```

## Base Classes & Patterns

### BaseController

All API controllers extend `BaseController` which configures the auth guard:

```php
// app/Http/Controllers/API/BaseController.php
abstract class BaseController
{
    protected ?string $guard;
    protected ?string $userModel;

    public function __construct()
    {
        $this->guard = config('auth.defaults.guard');
        Auth::shouldUse($this->guard);

        $provider = config("auth.guards.{$this->guard}.provider");
        $this->userModel = config("auth.providers.$provider.model");
    }
}
```

**Usage in controllers:**

```php
class UserController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn(User $user) => Media::delete($user->avatar));
    }
}
```

### BaseFormRequest

All form requests extend `BaseFormRequest` for consistent validation handling:

```php
// app/Http/Requests/BaseFormRequest.php
abstract class BaseFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace(collect($this->all())
            ->map(fn($value) => resolveEmptyToNull($value))
            ->toArray());
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        $firstMessage = collect($errors)->flatten()->first();

        throw new HttpResponseException(response()->json([
            'message' => $firstMessage,
            'errors' => $errors,
        ], 422));
    }
}
```

**Features:**
- Auto-converts empty strings to `null`
- Standardized error response format
- First error message as primary message

### BaseModel

Models extend `BaseModel` for shared functionality:

```php
// app/Models/BaseModel.php
abstract class BaseModel extends Model
{
    // Base model for shared logic
}
```

## Response Helpers

All responses use standardized helpers from `app/Helpers/App.php`:

```php
// Success response
return successResponse($data, __('api.created_success'), 201);
// Returns: { "status": true, "code": 201, "message": "...", "data": {...} }

// Error response
return failResponse(__('api.error'), [], 400);
// Returns: { "status": false, "code": 400, "message": "...", "data": [] }

// Paginated data
return successResponse(wrapPaginate($query, UserResource::class));
```

::: warning
Never use raw `response()->json()` — always use `successResponse()` or `failResponse()`.
:::

## Model Conventions

Models follow specific conventions for the permission system:

```php
class User extends Authenticatable
{
    use SoftDeletes, CreatedByObserver, LogsActivityOptions, HasRoles;

    // Include in permission generation
    public bool $inPermission = true;

    // Basic CRUD permissions
    public array $basicOperations = ['create', 'update', 'delete'];

    // Special permissions
    public array $specialOperations = [
        'view-all',
        'view-own',
        'restore',
        'force-delete',
        'toggle-active'
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }
}
```

**Generated Permissions:** `create-user`, `update-user`, `delete-user`, `view-all-user`, `view-own-user`, `restore-user`, `force-delete-user`, `toggle-active-user`

## Authentication & Authorization

### Authentication (Sanctum)

Token-based API authentication using Laravel Sanctum with custom guard:

```php
// Custom SanctumGuard handles token expiration
// app/Guards/SanctumGuard.php
protected function isValidAccessToken($accessToken): bool
{
    $last_used_at = $accessToken->last_used_at ?? $accessToken->created_at;

    return (!$this->expiration ||
        $last_used_at->gt(now()->subMinutes($this->expiration)));
}

// Login returns a token
$token = $user->createToken('api')->plainTextToken;

// Requests use: Authorization: Bearer <token>
// Middleware: auth:sanctum
```

### Authorization

Role-based access control (RBAC) using Spatie Permission:

```php
// Check roles
if ($user->hasRole('admin')) { ... }

// Check permissions
if ($user->hasPermissionTo('create-user')) { ... }

// In controllers, use Gate
Gate::authorize('create', User::class);
```

## Dependency Injection

The application uses Laravel's service container extensively:

```php
// Automatic injection in controllers
public function __construct(
    protected UserService $userService,
    protected NotificationService $notificationService,
    protected LoginService $loginService
) {}

// Or resolved on-demand
$service = app(UserService::class);
$service = resolve(UserService::class);
```

## Error Handling

Custom exceptions for consistent error responses:

```php
// Custom exceptions in app/Exceptions/
throw new InvalidEmailAndPasswordCombinationException(
    __('api.invalid_email_and_password'),
    Response::HTTP_NOT_ACCEPTABLE
);

throw new ModelAlreadyExistsException([
    'item' => new UserResource($model),
    'type' => User::class,
], __('validation.already_exists'), 433);

// Exception handler catches and returns JSON
{
  "status": false,
  "code": 406,
  "message": "Invalid email or password",
  "data": []
}
```

## Database Design

### Key Principles

1. **Soft Deletes** — Records marked as deleted, not physically removed
2. **Timestamps** — `created_at`, `updated_at` on all tables
3. **Created By** — `created_by` tracks who created each record
4. **Relationships** — Foreign keys for integrity
5. **Scopes** — Reusable query filters in `app/Scopes/`

## Real-Time Features

**Reverb WebSocket Server** for real-time notifications:

```php
// Broadcasting events
event(new NotificationEvent($user->id, $data));

// Channel naming convention
"notification.user.{$user_id}"

// Client receives real-time notifications
WebSocket connection → NotificationEvent → Client browser
```

## Background Jobs

Long-running tasks are queued:

```php
// Send notifications asynchronously
dispatch(new SendEmailJob($users, $data, ['email']));

// Jobs in app/Jobs/
// - SendEmailJob — Batch email sending
// - SendSmsJob — SMS via provider

// Run queue worker
php artisan queue:work
php artisan queue:work --queue=exports
```

## Configuration

Key configuration files:

| File | Purpose |
|------|---------|
| `config/project.php` | Project-specific settings |
| `config/roles.php` | Role definitions |
| `config/report.php` | Report page definitions |
| `config/app.php` | Application settings |
| `config/auth.php` | Authentication (Sanctum) |
| `config/permission.php` | Role/permission system |

## Next Steps

- [Authentication](/guide/authentication) — Deep dive into auth system
- [Helper Functions](/guide/helpers) — Available helper functions
- [Tools](/guide/tools/) — Package ecosystem overview
