---
title: Database Models
description: Core Eloquent models and their relationships in multi-tenant environment
---

# Database Models

This guide documents all core Eloquent models in the multi-tenant backend, their relationships, and how to use them.

## Overview

Models are organized into two categories:
- **Central Models** - Shared across all tenants (stored in central database)
- **Tenant Models** - Isolated per tenant (stored in tenant database)

## Central Models

### Tenant Model

**Location:** `app/Models/Central/Tenant.php`

Represents a tenant in the system.

**Properties:**
- `id` - Primary key
- `name` - Tenant name
- `domain` - Primary domain
- `database` - Tenant database name
- `is_active` - Active status
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Usage:**
```php
use App\Models\Central\Tenant;

// Get all active tenants
$tenants = Tenant::where('is_active', true)->get();

// Get tenant by domain
$tenant = Tenant::where('domain', 'acme.app.com')->first();

// Create new tenant
$tenant = Tenant::create([
    'name' => 'Acme Corp',
    'domain' => 'acme.app.com',
    'database' => 'acme_db',
    'is_active' => true,
]);
```

---

### Plan Model

**Location:** `app/Models/Central/Plan.php`

Represents subscription plans.

**Properties:**
- `id` - Primary key
- `name` - Plan name
- `slug` - URL slug
- `description` - Plan description
- `price` - Monthly price
- `billing_cycle` - Billing period (monthly, yearly)
- `is_active` - Active status
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Relationships:**
```php
// Get plan features
$plan->features(); // HasMany

// Get subscriptions for this plan
$plan->subscriptions(); // HasMany
```

**Usage:**
```php
use App\Models\Central\Plan;

// Get all active plans
$plans = Plan::where('is_active', true)->get();

// Get plan with features
$plan = Plan::with('features')->find($id);

// Create plan
$plan = Plan::create([
    'name' => 'Professional',
    'slug' => 'professional',
    'price' => 99.99,
    'billing_cycle' => 'monthly',
]);
```

---

### Subscription Model

**Location:** `app/Models/Central/Subscription.php`

Represents tenant subscriptions.

**Properties:**
- `id` - Primary key
- `tenant_id` - Tenant ID (foreign key)
- `plan_id` - Plan ID (foreign key)
- `status` - Subscription status (active, expired, cancelled)
- `started_at` - Start date
- `expires_at` - Expiration date
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Relationships:**
```php
// Get tenant
$subscription->tenant(); // BelongsTo

// Get plan
$subscription->plan(); // BelongsTo

// Get usage tracking
$subscription->usage(); // HasMany
```

**Usage:**
```php
use App\Models\Central\Subscription;

// Get active subscriptions
$active = Subscription::where('status', 'active')
    ->where('expires_at', '>', now())
    ->get();

// Get subscription with plan
$subscription = Subscription::with('plan')->find($id);

// Create subscription
$subscription = Subscription::create([
    'tenant_id' => $tenant->id,
    'plan_id' => $plan->id,
    'status' => 'active',
    'started_at' => now(),
    'expires_at' => now()->addMonth(),
]);
```

---

## Tenant Models

### User Model

**Location:** `app/Models/User.php`

Represents tenant users (automatically scoped to current tenant).

**Properties:**
- `id` - Primary key
- `name` - User full name
- `email` - User email (unique per tenant)
- `phone` - User phone number
- `password` - Hashed password
- `avatar` - Avatar file path
- `is_active` - User active status
- `email_verified_at` - Email verification timestamp
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `deleted_at` - Soft delete timestamp

**Relationships:**
```php
// Get user's roles
$user->roles(); // BelongsToMany

// Get user's permissions
$user->permissions(); // BelongsToMany

// Get user's personal access tokens
$user->tokens(); // HasMany

// Get user's activity logs
$user->activities(); // HasMany
```

**Usage:**
```php
use App\Models\User;

// Get all users (automatically scoped to current tenant)
$users = User::all();

// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

// Get user with roles
$user = User::with('roles')->find($id);

// Assign role
$user->assignRole('admin');
```

---

### Role Model

**Location:** `app/Models/Role.php`

Represents tenant roles (automatically scoped to current tenant).

**Properties:**
- `id` - Primary key
- `name` - Role name (translatable)
- `guard_name` - Guard name (default: 'user')
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Relationships:**
```php
// Get role's permissions
$role->permissions(); // BelongsToMany

// Get users with this role
$role->users(); // BelongsToMany
```

**Usage:**
```php
use App\Models\Role;

// Get all roles (scoped to current tenant)
$roles = Role::all();

// Create role
$role = Role::create([
    'name' => ['en' => 'Administrator', 'ar' => 'مسؤول'],
]);

// Assign permission to role
$role->givePermissionTo('create-users');
```

---

### Setting Model

**Location:** `app/Models/Setting.php`

Stores tenant-specific settings (automatically scoped to current tenant).

**Properties:**
- `id` - Primary key
- `key` - Setting key
- `group` - Setting group (dot notation)
- `value` - Setting value (JSON)
- `type` - Value type (text, textarea, boolean, etc.)
- `is_multi_lang` - Whether value is multi-language
- `label` - Display label
- `placeholder` - Input placeholder
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Usage:**
```php
use App\Models\Setting;

// Get all settings (scoped to current tenant)
$settings = Setting::all();

// Get setting by key
$siteName = Setting::where('key', 'name')
    ->where('group', 'general.info')
    ->first();

// Update setting
$setting->update(['value' => 'New Value']);

// Get through helper (tenant-scoped)
$value = setting('general.info.name');
```

---

### Notification Model

**Location:** `app/Models/Notification.php`

Stores tenant user notifications (automatically scoped to current tenant).

**Properties:**
- `id` - Primary key
- `user_id` - User ID (foreign key)
- `title` - Notification title
- `message` - Notification message
- `type` - Notification type
- `data` - Additional data (JSON)
- `is_read` - Read status
- `read_at` - Read timestamp
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Relationships:**
```php
// Get notification's user
$notification->user(); // BelongsTo
```

**Usage:**
```php
use App\Models\Notification;

// Get user's unread notifications (scoped to current tenant)
$unread = Notification::where('user_id', $user->id)
    ->where('is_read', false)
    ->get();

// Create notification
$notification = Notification::create([
    'user_id' => $user->id,
    'title' => 'Welcome',
    'message' => 'Welcome to our platform',
]);

// Mark as read
$notification->update(['is_read' => true, 'read_at' => now()]);
```

---

### ActivityLog Model

**Location:** `app/Models/ActivityLog.php`

Stores audit trail for tenant (automatically scoped to current tenant).

**Properties:**
- `id` - Primary key
- `log_name` - Log name
- `description` - Activity description
- `subject_type` - Model class name
- `subject_id` - Model ID
- `causer_type` - User model class
- `causer_id` - User ID
- `properties` - Changed properties (JSON)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Usage:**
```php
use App\Models\ActivityLog;

// Get all activity logs (scoped to current tenant)
$logs = ActivityLog::latest()->get();

// Get activity for specific model
$logs = ActivityLog::where('subject_type', User::class)
    ->where('subject_id', $userId)
    ->get();

// Get activity by user
$logs = ActivityLog::where('causer_id', $userId)->get();
```

---

## Tenant Scoping

All tenant models are automatically scoped to the current tenant. This is handled by middleware and model traits.

```php
// These queries are automatically scoped to current tenant
$users = User::all();           // Only current tenant's users
$settings = Setting::all();     // Only current tenant's settings
$notifications = Notification::all(); // Only current tenant's notifications

// No need to manually filter by tenant_id
```

---

## Model Relationships Diagram

```
Central Database:
├── Tenant
│   └── Subscriptions (HasMany)
├── Plan
│   ├── Features (HasMany)
│   └── Subscriptions (HasMany)
└── Subscription
    ├── Tenant (BelongsTo)
    └── Plan (BelongsTo)

Tenant Database (Per Tenant):
├── User
│   ├── Roles (BelongsToMany)
│   ├── Permissions (BelongsToMany)
│   ├── Tokens (HasMany)
│   └── Activities (HasMany)
├── Role
│   ├── Permissions (BelongsToMany)
│   └── Users (BelongsToMany)
├── Permission
│   ├── Roles (BelongsToMany)
│   └── Users (BelongsToMany)
├── Setting
│   └── (No relationships)
├── Notification
│   └── User (BelongsTo)
└── ActivityLog
    ├── Subject (MorphTo)
    └── Causer (MorphTo)
```

---

## Best Practices

1. **Remember tenant scoping** - All queries are automatically scoped
2. **Use relationships** - Load related data with `with()` to avoid N+1 queries
3. **Soft deletes** - Use soft delete for data recovery
4. **Activity logging** - Changes are automatically logged
5. **Translatable fields** - Use JSON for multi-language support
6. **Caching** - Cache frequently accessed data

---

## See Also

- [Multi-Tenancy](/guide/multitenancy/) — Multi-tenant architecture
- [Authentication](/guide/authentication) — User authentication
- [Authorization](/guide/authorization) — Role and permission management
- [Settings](/guide/features/settings) — Settings management
