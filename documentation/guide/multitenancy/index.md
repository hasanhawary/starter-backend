---
title: Multi-Tenancy Overview
description: Understanding the multi-tenant architecture with separate databases per tenant
---

# Multi-Tenancy Overview

The Laravel Starter Backend uses **Spatie Multitenancy** to implement a database-per-tenant architecture. This provides complete data isolation between tenants while sharing the application codebase.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Application                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────┐    ┌─────────────────────────────┐ │
│  │   Central (Landlord) │    │         Tenant              │ │
│  ├─────────────────────┤    ├─────────────────────────────┤ │
│  │ • Admin users        │    │ • Tenant users              │ │
│  │ • Tenants            │    │ • Tenant-specific data      │ │
│  │ • Plans              │    │ • Isolated per database     │ │
│  │ • Subscriptions      │    │                             │ │
│  │ • Global settings    │    │                             │ │
│  └──────────┬──────────┘    └──────────────┬──────────────┘ │
│             │                               │                │
│             ▼                               ▼                │
│  ┌─────────────────────┐    ┌─────────────────────────────┐ │
│  │   mysql connection   │    │     tenant connection       │ │
│  │   (central database) │    │   (per-tenant database)     │ │
│  └─────────────────────┘    └─────────────────────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Central vs Tenant

| Aspect | Central (Landlord) | Tenant |
|--------|-------------------|--------|
| **Purpose** | Platform administration | Tenant-specific data |
| **Database** | Single shared database | Separate database per tenant |
| **Connection** | `mysql` | `tenant` |
| **Routes** | `routes/central.php` | `routes/tenant.php` |
| **Models** | `app/Models/Central/` | `app/Models/Tenant/` |
| **Migrations** | `database/migrations/central/` | `database/migrations/tenant/` |
| **Users** | Admins (platform operators) | Users (tenant customers) |

## Central Database Contents

The central database stores platform-wide data:

| Table | Purpose |
|-------|---------|
| `admins` | Platform administrators |
| `tenants` | Registered tenant organizations |
| `plans` | Subscription plan definitions |
| `plan_prices` | Pricing tiers for plans |
| `plan_features` | Features included in plans |
| `subscriptions` | Tenant subscription records |
| `subscription_usages` | Usage tracking |
| `roles` | Permission roles |
| `permissions` | Available permissions |
| `settings` | Global platform settings |
| `countries` | Reference data (shared) |

## Tenant Database Contents

Each tenant has their own database with:

| Table | Purpose |
|-------|---------|
| `users` | Tenant's users |
| `roles` | Tenant-specific roles |
| `permissions` | Tenant-specific permissions |
| `settings` | Tenant settings |
| (+ any tenant-specific tables) | |

## How Tenant Detection Works

1. **Request arrives** at the application
2. **DomainTenantFinder** extracts the domain from the request
3. **Tenant lookup** finds matching tenant in `tenants` table
4. **Database switch** via `SwitchTenantDatabaseTask`
5. **Models auto-detect** connection using `Tenant::current()`

```php
// Domain-based tenant detection
// Request to: acme.example.com
// Matches tenant with domain = 'acme.example.com'
```

## Route Organization

### Central Routes (`routes/central.php`)

```php
Route::prefix('central')->group(function () {
    Route::post('login', LoginController::class);
    
    Route::middleware(['auth:sanctum'])->group(function () {
        // Admin management
        Route::apiResource('admins', AdminController::class);
        
        // Tenant management
        Route::apiResource('tenants', TenantController::class);
        
        // Subscription management
        Route::apiResource('plans', PlanController::class);
        Route::apiResource('subscriptions', SubscriptionController::class);
    });
});
```

### Tenant Routes (`routes/tenant.php`)

```php
Route::middleware('tenant')->group(function() {
    Route::post('login', LoginController::class);
    
    Route::middleware(['auth:sanctum'])->group(function () {
        // Tenant user management
        Route::apiResource('users', UserController::class);
        
        // Tenant-specific features
        Route::get('me', [ProfileController::class, 'user']);
    });
});
```

## Model Organization

### Central Models (`app/Models/Central/`)

```
Central/
├── Admin.php           # Platform administrators
├── Tenant.php          # Tenant organizations
├── Plan.php            # Subscription plans
├── PlanPrice.php       # Plan pricing
├── PlanFeature.php     # Plan features
├── Subscription.php    # Tenant subscriptions
├── SubscriptionUsage.php
├── Role.php            # Spatie roles
├── Permission.php      # Spatie permissions
├── Setting.php         # Global settings
├── Country.php         # Reference data
└── Notification.php    # Admin notifications
```

### Tenant Models (`app/Models/Tenant/`)

```
Tenant/
└── User.php            # Tenant users
```

## Directory Structure

Files are organized by Central/Tenant context:

```
app/
├── Http/
│   ├── Controllers/API/
│   │   ├── Central/        # Admin controllers
│   │   │   ├── Admin/
│   │   │   ├── Auth/
│   │   │   ├── Subscription/
│   │   │   └── Tenant/
│   │   └── Tenant/         # Tenant controllers
│   │       ├── Auth/
│   │       └── User/
│   ├── Requests/
│   │   ├── Central/
│   │   └── Tenant/
│   └── Resources/
│       ├── Central/
│       └── Tenant/
├── Models/
│   ├── Central/
│   └── Tenant/
├── Filters/
│   ├── Central/
│   └── Tenant/
└── Policies/
    ├── Central/
    └── Tenant/
```

## Creating a New Tenant

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();

$tenant = $service->createTenant([
    'name' => 'Acme Corp',
    'domain' => 'acme.example.com',
    'database' => 'tenant_acme',
], $planPriceId);

// Run migrations for the new tenant database
Artisan::call('tenants:artisan', [
    'artisanCommand' => 'migrate --path=database/migrations/tenant --database=tenant',
    '--tenant' => $tenant->id,
]);
```

## Accessing Current Tenant

```php
use Spatie\Multitenancy\Models\Tenant;

// Get current tenant
$tenant = Tenant::current();

// Check if in tenant context
if (Tenant::current()) {
    // Tenant context
} else {
    // Central context
}
```

## See Also

- [Tenant Models](/guide/multitenancy/models)
- [Migrations](/guide/multitenancy/migrations)
- [Tenant Management](/guide/multitenancy/tenants)
- [Multi-Tenancy Configuration](/guide/configuration/multitenancy)
