---
title: Architecture
description: Understanding the Landing Dashboard Kit architecture and project structure
---

# Architecture Overview

The Landing Dashboard Kit is built on a **modular, service-oriented architecture** designed for scalability, maintainability, and rapid development.

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
        ->setGuard('user')
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

## Authentication & Authorization

### Authentication (Sanctum)

Token-based API authentication using Laravel Sanctum:

```php
// Login returns a token
$token = $user->createToken('user')->plainTextToken;

// Requests use: Authorization: Bearer <token>
// Middleware: auth:sanctum
```

### Authorization

Role-based access control (RBAC) using Spatie Permission:

```php
// Check roles
if ($user->hasRole('admin')) { ... }

// Check permissions
if ($user->hasPermissionTo('create_users')) { ... }
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
// In services
throw new InvalidEmailAndPasswordCombinationException(
    __('api.invalid_email_and_password'),
    Response::HTTP_NOT_ACCEPTABLE
);

// Exception handler catches and returns JSON
{
  "success": false,
  "message": "Invalid email or password",
  "errors": null
}
```

## Database Design

### Key Principles

1. **Soft Deletes** — Records marked as deleted, not physically removed
2. **Timestamps** — `created_at`, `updated_at` on all tables
3. **Relationships** — Foreign keys for integrity
4. **Scopes** — Reusable query filters

## Real-Time Features

**Reverb WebSocket Server** for real-time notifications:

```php
// Broadcasting events
event(new NotificationEvent($user->id, $data));

// Client receives real-time notifications
WebSocket connection → NotificationEvent → Client browser
```

## Background Jobs

Long-running tasks are queued:

```php
// Send email asynchronously
dispatch(new SendEmailJob($user));

// Generate export in background
dispatch(new ExportJob($filters))->onQueue('exports');

// View job queue
php artisan queue:work
```

## Configuration

Key configuration files:

- `config/app.php` — Application settings
- `config/auth.php` — Authentication (Sanctum)
- `config/permission.php` — Role/permission system
- `config/project.php` — Project-specific settings (OTP, uploads, etc.)
- `config/lang.php` — Multi-language configuration
- `config/roles.php` — Role definitions
- `config/report.php` — Report page definitions

---

## Model Conventions

Models follow specific conventions for permissions, translations, and activity logging.

### Standard Model Structure

```php
<?php

namespace App\Models;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Country extends BaseModel
{
    use HasTranslations, SoftDeletes;
    
    // ─── Translations ───────────────────────────────
    public array $translatable = ['name', 'nationality'];
    
    // ─── Permission Configuration ───────────────────
    public bool $inPermission = true;                    // Include in permission system
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['restore', 'force-delete'];
    
    // ─── Fillable ───────────────────────────────────
    protected $fillable = [
        'name', 'nationality', 'flag', 'code', 
        'phone_code', 'phone_length', 'is_active',
    ];
    
    // ─── Casts ──────────────────────────────────────
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // ─── Media Attributes ───────────────────────────
    public function setFlagAttribute($value): void
    {
        $path = Media::replace($this->flag ?? null)->upload($value, 'flags');
        $this->attributes['flag'] = $path;
    }
    
    public function flag(): Attribute
    {
        return Attribute::make(get: fn($value) => Media::url($value));
    }
    
    // ─── Scopes ─────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
```

### Permission Properties

| Property | Type | Description |
|----------|------|-------------|
| `$inPermission` | `bool` | Include model in permission system |
| `$basicOperations` | `array` | Standard CRUD permissions |
| `$specialOperations` | `array` | Additional permissions (restore, toggle, etc.) |

**Generated permissions:** `create-country`, `update-country`, `delete-country`, `restore-country`, `force-delete-country`

### User Model Example

```php
class User extends Authenticatable implements LdapAuthenticatable
{
    use SoftDeletes, AuthenticatesWithLdap, HasApiTokens, HasRoles;
    use LogsActivityOptions, CreatedByObserver, ApplyNotification;

    protected string $guard_name = 'user';
    
    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];

    protected $fillable = [
        'name', 'email', 'phone_code_id', 'phone', 'avatar', 
        'gender', 'password', 'is_active', 'last_login', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'gender' => UserGenderEnum::class,
    ];
}
```

---

## Enum Conventions

Enums use the `EnumMethods` trait from LookupManager for array conversion.

```php
<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ActiveTypeEnum: int
{
    use EnumMethods;

    case Active = 1;
    case InActive = 0;
}
```

**Available enums:**
- `App\Enum\Global\ActiveTypeEnum` — Active/Inactive states
- `App\Enum\Global\OtpTypeEnum` — OTP delivery methods
- `App\Enum\Global\SettingTypeEnum` — Setting field types
- `App\Enum\User\UserGenderEnum` — Gender options

---

## Next Steps

- [Configuration](/guide/configuration) — Detailed config documentation
- [Authentication](/guide/authentication) — Deep dive into auth system
- [Tools](/guide/tools/) — Package ecosystem overview
