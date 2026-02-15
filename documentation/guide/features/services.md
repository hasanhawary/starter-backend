---
title: Services & Business Logic
description: Core services handling authentication, notifications, settings, and more
---

# Services & Business Logic

This guide documents all services in the dashboard backend that handle core business logic.

## Overview

Services encapsulate business logic and are located in `app/Services/`. They're organized by domain (Auth, Global) and provide reusable functionality across controllers.

## Authentication Services

### LoginService

**Location:** `app/Services/Auth/LoginService.php`

Handles user login with support for email/password and LDAP authentication.

**Methods:**

```php
public function attempt(?array $data): array
```

Authenticates user with email and password. Supports LDAP fallback. Returns array with user and token.

**Usage:**
```php
use App\Services\Auth\LoginService;

$service = app(LoginService::class);

try {
    $result = $service->attempt([
        'email' => 'user@example.com',
        'password' => 'password123'
    ]);
    
    return successResponse([
        'user' => new UserResource($result['user']),
        'token' => $result['token'],
    ]);
} catch (InvalidEmailAndPasswordCombinationException $e) {
    return failResponse($e->getMessage(), 401);
} catch (InActiveUserException $e) {
    return failResponse($e->getMessage(), 403);
}
```

**Features:**
- Email/password validation
- LDAP integration with fallback
- User active status check
- OTP verification support
- Last login tracking
- Activity logging

---

### OTPService

**Location:** `app/Services/Auth/OTPService.php`

Handles OTP generation, verification, and management.

**Methods:**

```php
public function send(SendOtpRequest $request, string $type = 'login'): string
```

Generates and sends OTP for the specified type via email notification.

**Parameters:**
- `$request` - SendOtpRequest with email
- `$type` - OTP type (login, reset_password, verify_email)

**Returns:** Generated OTP code

**Usage:**
```php
use App\Services\Auth\OTPService;

$service = app(OTPService::class);

// Generate and send OTP for login
$otp = $service->send(new SendOtpRequest(['email' => 'user@example.com']), 'login');
```

---

```php
public function verify($request, string $type = 'login'): mixed
```

Verifies OTP and clears it after successful validation.

**Parameters:**
- `$request` - Request with email and otp
- `$type` - OTP type

**Returns:** User model if valid, throws InvalidOtpException if invalid

**Usage:**
```php
try {
    $user = $service->verify(new VerifyOtpRequest(['email' => 'user@example.com', 'otp' => '123456']), 'login');
    // OTP is valid and cleared
} catch (InvalidOtpException $e) {
    return failResponse('Invalid OTP', 422);
}
```

---

```php
public function check($request, string $type = 'login'): mixed
```

Validates OTP without consuming it (useful for multi-step flows).

**Usage:**
```php
try {
    $user = $service->check(new VerifyOtpRequest([...]), 'login');
    // OTP is valid but not cleared
} catch (InvalidOtpException $e) {
    return failResponse('Invalid OTP', 422);
}
```

---

**Configuration:**
```php
// config/project.php
'otp' => [
    'length' => 6,
    'type' => 'numeric',        // numeric, alpha, alphanumeric
    'expires_in' => 10,         // minutes
    'max_attempts' => 5,
    'lock_time' => 120,         // seconds
    'delay' => 30,              // seconds between resends
    'default' => null,          // Set to fixed OTP for testing
]
```

---

### ResetPasswordService

**Location:** `app/Services/Auth/ResetPasswordService.php`

Handles password reset flow using OTP verification.

**Methods:**

```php
public function reset(ResetPasswordRequest $request): bool
```

Resets password using OTP verification. Clears OTP after successful reset.

**Parameters:**
- `$request` - ResetPasswordRequest with email, otp, and password

**Returns:** true if successful, throws InvalidOtpException if OTP invalid

**Usage:**
```php
use App\Services\Auth\ResetPasswordService;

$service = app(ResetPasswordService::class);

try {
    $service->reset(new ResetPasswordRequest([
        'email' => 'user@example.com',
        'otp' => '123456',
        'password' => 'newpassword123'
    ]));
    return successResponse(null, 'Password reset successfully');
} catch (InvalidOtpException $e) {
    return failResponse('Invalid OTP', 422);
}
```

**Features:**
- OTP-based password reset
- Password validation
- Automatic OTP clearing after reset

---

### ThrottleService

**Location:** `app/Services/Auth/ThrottleService.php`

Handles rate limiting for login attempts using Laravel's RateLimiter.

**Methods:**

```php
public function ensureIsNotRateLimited(string $key, int $maxAttempts = 3): void
```

Checks if rate limit exceeded. Throws ValidationException if limit reached.

**Usage:**
```php
use App\Services\Auth\ThrottleService;

$service = app(ThrottleService::class);

try {
    $key = $service->generateThrottleKey('user@example.com', request()->ip());
    $service->ensureIsNotRateLimited($key, 5);
} catch (ValidationException $e) {
    return failResponse('Too many login attempts. Try again later.', 429);
}
```

---

```php
public function incrementRateLimit(string $key, int $decaySeconds = 400): void
```

Increments rate limit counter.

**Usage:**
```php
$service->incrementRateLimit($key, 400);
```

---

```php
public function clearRateLimit(string $key): void
```

Clears rate limit counter after successful login.

**Usage:**
```php
$service->clearRateLimit($key);
```

---

```php
public function generateThrottleKey(string $email, string $ip): string
```

Generates unique throttle key combining email and IP.

**Usage:**
```php
$key = $service->generateThrottleKey('user@example.com', request()->ip());
```

---

## Global Services

### SettingService

**Location:** `app/Services/Global/SettingService.php`

Manages application settings with caching and multi-language support.

**Methods:**

```php
public function all(): array
```

Retrieves all settings as nested associative array with caching.

**Usage:**
```php
use App\Services\Global\SettingService;

$service = app(SettingService::class);

// Get all settings
$allSettings = $service->all();
```

---

```php
public function get(string $path, $lang = null, $default = null): mixed
```

Retrieves setting value by dot-notation path with optional language.

**Usage:**
```php
// Get single setting
$siteName = $service->get('general.info.name');

// Get with default
$logo = $service->get('properties.website_logo_large', null, '/images/default-logo.png');

// Get in specific language
$nameAr = $service->get('general.info.name', 'ar');
$nameEn = $service->get('general.info.name', 'en');
```

---

```php
public function clearCache(): void
```

Clears settings cache to force reload from database.

**Usage:**
```php
$service->clearCache();
```

---

**Caching:**
```php
// Settings are cached forever per brand
// Cache key: "settings_{brand_name}"

// Clear cache after updating settings
$service->clearCache();
```

---

### NotificationService

**Location:** `app/Services/Global/NotificationService.php`

Handles multi-channel notifications (email, SMS, in-app, real-time).

**Methods:**

```php
public static function resolve(Authenticatable $user, array $data, ?array $types = ['notify', 'realtime']): void
```

Sends notification via specified channels.

**Parameters:**
- `$user` - User model
- `$data` - Notification data (title, msg, url, etc.)
- `$types` - Array of channels: 'notify', 'email', 'sms', 'realtime'

**Usage:**
```php
use App\Services\Global\NotificationService;

// Send via in-app and real-time
NotificationService::resolve(
    user: $user,
    data: [
        'title' => 'Welcome',
        'msg' => 'Welcome to our platform',
        'url' => '/dashboard',
        'urlText' => 'Go to Dashboard'
    ],
    types: ['notify', 'realtime']
);

// Send via email only
NotificationService::resolve(
    user: $user,
    data: [
        'title' => 'Password Reset',
        'msg' => 'Click the link to reset your password'
    ],
    types: ['email']
);

// Send via SMS only
NotificationService::resolve(
    user: $user,
    data: ['msg' => 'Your OTP is: 123456'],
    types: ['sms']
);
```

---

```php
public static function sendEmail(Authenticatable $user, array $data): void
```

Sends email notification (queued).

**Usage:**
```php
NotificationService::sendEmail(
    user: $user,
    data: [
        'title' => 'Password Reset',
        'msg' => 'Click here to reset your password'
    ]
);
```

---

**Configuration:**
```php
// config/project.php
'realtime' => [
    'enable' => env('REVERB_ENABLED', true),
]
```

---

### EncryptionService

**Location:** `app/Services/Global/EncryptionService.php`

Handles data encryption and decryption for sensitive fields.

**Methods:**

```php
public function encrypt(string $value): string
```

Encrypts sensitive data.

**Usage:**
```php
use App\Services\Global\EncryptionService;

$service = app(EncryptionService::class);

$encrypted = $service->encrypt('sensitive-data');
```

---

```php
public function decrypt(string $value): string
```

Decrypts encrypted data.

**Usage:**
```php
$decrypted = $service->decrypt($encrypted);
```

---

### QueryHelper

**Location:** `app/Services/Global/QueryHelper.php`

Provides utilities for JSON field searching and filtering.

**Methods:**

```php
public function searchJson(Builder $query, string $column, string $search): Builder
```

Searches JSON column across all locales.

**Usage:**
```php
use App\Services\Global\QueryHelper;

$helper = app(QueryHelper::class);

// Search in translatable name field
$results = $helper->searchJson(
    query: User::query(),
    column: 'name',
    search: 'john'
);
```

---

```php
public function getJsonValue(array $data, string $locale = null): string
```

Retrieves value from JSON data in specified locale.

**Usage:**
```php
$name = $helper->getJsonValue(
    data: ['en' => 'John', 'ar' => 'جون'],
    locale: 'en'
);
// Result: 'John'
```

---

## Service Injection

### Using Services in Controllers

```php
<?php

namespace App\Http\Controllers\API\Global\Auth;

use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private LoginService $loginService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginService->attempt($request->validated());

            return successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ]);
        } catch (InvalidEmailAndPasswordCombinationException $e) {
            return failResponse($e->getMessage(), 401);
        } catch (InActiveUserException $e) {
            return failResponse($e->getMessage(), 403);
        }
    }
}
```

---

### Using Services in Jobs

```php
<?php

namespace App\Jobs;

use App\Services\Global\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private $user,
        private array $data
    ) {}

    public function handle(): void
    {
        NotificationService::resolve($this->user, $this->data);
    }
}
```

---

### Using Services in Events

```php
<?php

namespace App\Events;

use App\Services\Global\NotificationService;
use Illuminate\Foundation\Events\Dispatchable;

class UserCreated
{
    use Dispatchable;

    public function __construct(public $user) {}

    public function handle(): void
    {
        NotificationService::resolve(
            $this->user,
            [
                'title' => 'welcome_title',
                'msg' => 'welcome_msg|name=' . $this->user->name,
                'url' => '/dashboard',
                'urlText' => 'Go to Dashboard'
            ],
            ['notify', 'email']
        );
    }
}
```

---

## Best Practices

1. **Inject services** - Use constructor injection for services
2. **Handle exceptions** - Catch and handle service exceptions
3. **Use transactions** - Wrap multi-step operations in transactions
4. **Cache results** - Cache expensive operations
5. **Log operations** - Log important service operations
6. **Test services** - Write unit tests for services
7. **Keep services focused** - Each service should have a single responsibility
8. **Document methods** - Add PHPDoc comments to service methods

---

## See Also

- [Authentication](/guide/authentication) — Authentication guide
- [Notifications](/guide/features/notifications) — Notification system
- [Settings](/guide/features/settings) — Settings management
- [Jobs](/guide/features/jobs) — Background jobs
- [Events](/guide/features/events) — Event system
