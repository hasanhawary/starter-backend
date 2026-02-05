---
title: Configuration Overview
description: Complete guide to all configuration files in the Laravel Starter Backend
---

# Configuration Overview

This guide covers all configuration files in the Laravel Starter Backend project. Configuration files are located in the `config/` directory.

## Configuration Files

| File | Purpose |
|------|---------|
| `multitenancy.php` | Multi-tenancy settings, tenant finder, database switching |
| `project.php` | Project info, auth settings, LDAP, OTP configuration |
| `roles.php` | Role/permission definitions, inheritance rules |
| `auth.php` | Authentication guards and providers |
| `database.php` | Database connections (mysql, tenant) |
| `reverb.php` | WebSocket server configuration |
| `report.php` | Report builder settings |
| `brands.php` | Multi-brand support settings |
| `activitylog.php` | Activity logging configuration |
| `permission.php` | Spatie permission settings |

## Core Configuration Files

### Multi-Tenancy (`config/multitenancy.php`)

Controls the multi-tenant architecture:

```php
return [
    'tenant_finder' => DomainTenantFinder::class,
    'switch_tenant_tasks' => [
        SwitchTenantDatabaseTask::class,
    ],
    'tenant_database_connection_name' => 'tenant',
    'landlord_database_connection_name' => 'mysql',
    'queues_are_tenant_aware_by_default' => true,
];
```

[Learn more about Multi-Tenancy Configuration →](/guide/configuration/multitenancy)

### Project Settings (`config/project.php`)

Application-wide settings including authentication, LDAP, and OTP:

```php
return [
    'project' => [
        'name' => env('APP_NAME', 'MyProject'),
        'locale' => 'ar',
        'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
    ],
    'auth' => [
        'login_methods' => ['password' => true, 'otp' => true],
        'max_login_attempts' => 5,
    ],
    'ldap' => [
        'active' => env('LDAP_ACTIVE', false),
    ],
    'otp' => [
        'length' => 6,
        'expires_in' => 10,
    ],
];
```

[Learn more about Project Configuration →](/guide/configuration/project)

### Roles & Permissions (`config/roles.php`)

Define roles with permission inheritance:

```php
return [
    'class_paths' => [
        'role' => \App\Models\Central\Role::class,
        'permission' => \App\Models\Central\Permission::class,
    ],
    'default_guard' => 'sanctum',
    'roles' => [
        'default_role' => [
            'type' => null,
            'permissions' => [],
        ],
    ],
];
```

[Learn more about Roles Configuration →](/guide/configuration/roles)

### Authentication (`config/auth.php`)

Guards and providers for Central and Tenant authentication:

```php
'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',      // Tenant users
    ],
    'admin' => [
        'driver' => 'sanctum',
        'provider' => 'admins',     // Central admins
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => \App\Models\Tenant\User::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => \App\Models\Central\Admin::class,
    ],
],
```

### Database (`config/database.php`)

Two primary connections for multi-tenancy:

```php
'connections' => [
    'mysql' => [
        // Landlord/Central database
        'database' => env('DB_DATABASE', 'laravel'),
    ],
    'tenant' => [
        // Tenant database (dynamically switched)
        'database' => null,  // Set at runtime
    ],
],
```

## Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME="Laravel Starter"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Africa/Cairo

# Database (Central/Landlord)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=starter_central
DB_USERNAME=root
DB_PASSWORD=

# Authentication
AUTH_LOGIN_OTP=true

# LDAP (Optional)
LDAP_ACTIVE=false
LDAP_HOST=
LDAP_BASE_DN=

# Real-time (Reverb)
REALTIME=true
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
```

## See Also

- [Multi-Tenancy Configuration](/guide/configuration/multitenancy)
- [Project Configuration](/guide/configuration/project)
- [Roles Configuration](/guide/configuration/roles)
- [Installation Guide](/guide/installation)
