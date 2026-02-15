---
title: Configuration
description: Application configuration files and settings
---

# Configuration Guide

This page documents all configuration files and their options in the Admin Dashboard Kit project.

## Project Configuration (`config/project.php`)

The main project configuration file controlling application behavior.

### Project Settings

```php
'project' => [
    'name' => env('APP_NAME', 'MyProject'),
    'version' => env('APP_VERSION', '1.0.0'),
    'env' => env('APP_ENV', 'production'),
    'locale' => 'ar',              // Default language
    'fallback_locale' => 'en',
    'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
    'currency' => 'EGP',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
    'datetime_format' => 'Y-m-d H:i:s',
],
```

### Authentication Settings

```php
'auth' => [
    'login_methods' => [
        'password' => true,
        'otp' => env('AUTH_LOGIN_OTP', false),
    ],
    'encryption' => [
        'key' => env('FRONT_SHARED_KEY', 'default_secret_key'),
        'incoming' => [
            'password' => false,
            'otp' => false,
        ],
        'outgoing' => [
            'roles' => true,
            'permissions' => true,
            'token' => true,
            'user_data' => false,
        ],
    ],
    'otp' => [
        'required_for' => [
            'admin' => false,
            'user' => false,
        ],
        'fallback_to_password' => true,
    ],
    'max_login_attempts' => 5,
    'lockout_time' => 180,          // seconds
    'default_role' => 'default_role',
    'default_phone_code_id' => 1,
],
```

### LDAP Configuration

```php
'ldap' => [
    'active' => env('LDAP_ACTIVE', false),
    'type' => env('LDAP_TYPE', 'ad'),     // 'ad' or 'openldap'
    'local' => env('LDAP_LOCAL', true),   // true = OpenLDAP, false = AD
],
```

### OTP Configuration

```php
'otp' => [
    'default' => '1111',        // Fixed OTP for testing (set null in production)
    'length' => 6,              // Number of characters
    'type' => 'alpha',          // 'numeric' | 'alpha' | 'alphanumeric'
    'delay' => '30',            // Seconds between sends
    'expires_in' => 10,         // Minutes until expiry
    'max_attempts' => 1,
    'lock_time' => 120,         // Seconds
],
```

### Other Settings

```php
'pagination' => [
    'per_page' => 15,
    'max' => 100,
],

'uploads' => [
    'disk' => env('FILESYSTEM_DISK', 'public'),
    'max_size' => 2048,         // KB
    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'],
],

'cache' => [
    'default' => env('CACHE_DRIVER', 'file'),
    'ttl' => 60,                // Minutes
],

'realtime' => [
    'enabled' => env('REALTIME_ENABLED', false),
],

'notifications' => [
    'from_email' => env('MAIL_FROM_ADDRESS', 'no-reply@myproject.com'),
    'from_name' => env('MAIL_FROM_NAME', 'MyProject'),
    'sms_provider' => env('SMS_PROVIDER', 'twilio'),
],
```

## Roles Configuration (`config/roles.php`)

Configures the Permission Manager package for role-based access control.

### Class Paths

```php
'class_paths' => [
    'role' => \App\Models\Role::class,
    'permission' => \App\Models\Permission::class,
],
```

### Default Guard

```php
'default_guard' => 'sanctum',
```

### Role Definitions

```php
'roles' => [
    'default_role' => [
        'home' => ['report'],
        'type' => null,
        'permissions' => [],
    ],
    'manager' => [
        'like' => 'admin',              // Inherit from admin
        'type' => 'exception',          // Remove specific permissions
        'permissions' => [
            'users' => ['read', 'update'],
        ],
    ],
],
```

**Role Configuration Options:**
- `like` — Inherit permissions from another role
- `type` — `'exception'` (remove) or `'added'` (add to inherited)
- `permissions` — `'basic'` for CRUD, `'*'` for all, or specific array

### Additional Operations

```php
'additional_operations' => [
    [
        'name' => 'Special Permissions',
        'operations' => ['export', 'import'],
        'basic' => true,    // Include basic operations
    ],
],
```

## Report Configuration (`config/report.php`)

Defines report pages and their components for the Report Builder.

```php
return [
    'namespace' => 'App\Tools\Report',

    'pages' => [
        'user' => [
            'type' => 'page',
            'report' => [
                'cards' => [
                    'type' => 'card',
                    'size' => ['cols' => '6', 'md' => '3', 'lg' => '3'],
                ],
                'registered_users_by_date' => [
                    'type' => 'spline',
                    'size' => ['cols' => '12', 'md' => '12', 'lg' => '12'],
                ],
                'user_by_gender' => [
                    'type' => 'spline',
                    'size' => ['cols' => '12', 'md' => '12', 'lg' => '12'],
                ],
            ],
        ],
    ],
];
```

**Chart Types:** `card`, `spline`, `bar`, `pie`, `line`, `area`

## Laravel Configuration Files

### Core Files

| File | Purpose |
|------|---------|
| `config/app.php` | App name, timezone, locale, providers |
| `config/auth.php` | Guards, providers, password reset |
| `config/database.php` | Database connections |
| `config/mail.php` | Mailer settings |
| `config/queue.php` | Queue drivers and retry settings |
| `config/cache.php` | Cache stores and drivers |
| `config/filesystems.php` | Storage disks |
| `config/sanctum.php` | Sanctum token settings |

### Package Configs

| File | Purpose |
|------|---------|
| `config/permission.php` | Spatie Permission settings |
| `config/activitylog.php` | Spatie Activity Log settings |
| `config/reverb.php` | WebSocket server configuration |
| `config/brands.php` | Multi-brand support |

## Environment Variables (`.env`)

### Application

```env
APP_NAME="Admin Dashboard Kit"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Africa/Cairo
APP_URL=http://localhost:8000
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=starter_backend
DB_USERNAME=root
DB_PASSWORD=
```

### Authentication

```env
AUTH_LOGIN_OTP=false
FRONT_SHARED_KEY=your_encryption_key
```

### LDAP (Optional)

```env
LDAP_ACTIVE=false
LDAP_TYPE=ad
LDAP_LOCAL=true
LDAP_HOST=ldap.example.com
LDAP_BASE_DN=dc=example,dc=com
LDAP_ADMIN_USERNAME=admin@example.com
LDAP_ADMIN_PASSWORD=secret
```

### Mail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Reverb (WebSocket)

```env
REALTIME_ENABLED=true
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=9000
REVERB_SCHEME=http
```

### Queue

```env
QUEUE_CONNECTION=database
# or for Redis:
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## See Also

- [Settings & Configuration](/guide/features/settings) — Runtime settings management
- [Installation](/guide/installation) — Initial setup
- [Permission Manager](/guide/tools/permission-manager) — Role configuration details
