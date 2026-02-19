---
title: Services Overview
description: Business logic services for authentication, notifications, and more
---

# Services Overview

Services contain the core business logic of the application, separated from controllers and models for better organization and testability.

## Directory Structure

```
app/Services/
├── Auth/                       # Authentication services
│   ├── BaseAuthService.php     # Base class for auth services
│   ├── LoginService.php        # Login with LDAP/OTP support
│   ├── OTPService.php          # OTP generation & verification
│   ├── ResetPasswordService.php # Password reset flow
│   └── ThrottleService.php     # Rate limiting
├── Global/                     # Application-wide services
│   ├── EncryptionService.php   # Data encryption utilities
│   ├── NotificationService.php # Multi-channel notifications
│   ├── QueryHelper.php         # Query building utilities
│   └── SettingService.php      # Settings management
└── Tenant/                     # Tenant-specific services
    └── TenantService.php       # Tenant CRUD with subscriptions
```

## Auth Services

Handle all authentication-related logic:

| Service | Purpose |
|---------|---------|
| `LoginService` | Login with password, LDAP, and OTP support |
| `OTPService` | Generate, send, and verify OTPs |
| `ResetPasswordService` | Password reset via OTP |
| `ThrottleService` | Rate limiting for login attempts |

```php
use App\Services\Auth\LoginService;

$loginService = new LoginService(new OTPService());
$result = $loginService
    ->setGuard('user')
    ->setModel(User::class)
    ->attempt($credentials);

// Returns ['user' => User, 'token' => 'Bearer token']
```

[Learn more about Auth Services →](/guide/services/auth-services)

## Global Services

Application-wide utilities:

| Service | Purpose |
|---------|---------|
| `NotificationService` | Send notifications via multiple channels |
| `SettingService` | Cached settings management |
| `EncryptionService` | Encrypt/decrypt sensitive data |

```php
use App\Services\Global\NotificationService;

NotificationService::resolve($user, [
    'type' => 'welcome',
    'msg' => 'Welcome to the platform!',
], ['notify', 'email']);
```

[Learn more about Global Services →](/guide/services/global-services)

## Tenant Services

Tenant management operations:

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();
$tenant = $service->createTenant([
    'name' => 'Acme Corp',
    'domain' => 'acme.example.com',
    'database' => 'tenant_acme',
], $planPriceId);
```

[Learn more about Tenant Services →](/guide/services/tenant-services)

## Service Patterns

### Dependency Injection

Services are injected via constructor:

```php
class LoginController extends BaseController
{
    public function __construct(
        protected LoginService $loginService,
        protected ThrottleService $throttleService
    ) {
        parent::__construct();
    }
}
```

### Fluent Interface

Many services support method chaining:

```php
$loginService
    ->setGuard('admin')
    ->setModel(Admin::class)
    ->attempt($credentials);
```

### Static Methods

Some services provide static methods for convenience:

```php
// NotificationService
NotificationService::resolve($user, $data, $channels);

// SettingService
SettingService::get('app.name');
```

## Creating a Service

```php
// app/Services/MyDomain/MyService.php
namespace App\Services\MyDomain;

class MyService
{
    public function __construct(
        protected SomeDependency $dependency
    ) {}

    public function doSomething(array $data): mixed
    {
        // Business logic here
    }
}
```

Register in a service provider if needed:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(MyService::class, function ($app) {
        return new MyService($app->make(SomeDependency::class));
    });
}
```

## See Also

- [Auth Services](/guide/services/auth-services)
- [Global Services](/guide/services/global-services)
- [Tenant Services](/guide/services/tenant-services)
- [Architecture](/guide/architecture)
