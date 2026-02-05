---
title: Configuration
description: Application configuration files and settings
---

# Configuration Guide

This page documents all configuration files and their settings. The project uses several custom config files beyond Laravel defaults.

## Project Configuration (`config/project.php`)

The main application configuration file controlling authentication, OTP, uploads, and more.

### Project Settings

```php
'project' => [
    'name' => env('APP_NAME', 'MyProject'),
    'version' => env('APP_VERSION', '1.0.0'),
    'locale' => 'ar',                    // Default language
    'fallback_locale' => 'en',
    'timezone' => 'Africa/Cairo',
    'currency' => 'EGP',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
]
```

### Authentication Settings

```php
'auth' => [
    'login_methods' => [
        'password' => true,              // Enable password login
        'otp' => env('AUTH_LOGIN_OTP', true),  // Enable OTP login
    ],
    'encryption' => [
        'key' => env('FRONT_SHARED_KEY'),
        'incoming' => ['password' => false, 'otp' => false],
        'outgoing' => ['roles' => true, 'permissions' => true, 'token' => true],
    ],
    'otp' => [
        'required_for' => ['admin' => false, 'user' => false],
        'fallback_to_password' => true,
    ],
    'max_login_attempts' => 5,
    'lockout_time' => 180,               // Seconds
    'default_role' => 'default_role',
]
```

### LDAP Configuration

```php
'ldap' => [
    'active' => env('LDAP_ACTIVE', false),
    'type' => env('LDAP_TYPE', 'ad'),    // 'ad' or 'openldap'
    'local' => env('LDAP_LOCAL', true),  // true = OpenLDAP, false = AD
]
```

### OTP Settings

```php
'otp' => [
    'default' => '1111',                 // Fixed OTP for testing
    'length' => 6,
    'type' => 'alpha',                   // numeric | alpha | alphanumeric
    'delay' => '30',                     // Seconds between sends
    'expires_in' => 10,                  // Minutes
    'max_attempts' => 1,
    'lock_time' => 120,                  // Seconds
]
```

### Pagination & Uploads

```php
'pagination' => [
    'per_page' => 15,
    'max' => 100,
],
'uploads' => [
    'disk' => env('FILESYSTEM_DISK', 'public'),
    'max_size' => 2048,                  // KB
    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'],
]
```

---

## Language Configuration (`config/lang.php`)

Controls multi-language support and validation rules.

```php
return [
    'available' => ['ar', 'en'],         // Available languages
    'default' => 'en',                   // Default language
    'required_languages' => ['ar', 'en'],
    
    // Validation rules per language
    'languages_validation' => [
        'ar' => 'required',              // Arabic is required
        'en' => 'sometimes',             // English is optional
    ],
];
```

**Usage in validation:**
```php
use App\Rules\TranslatableRequired;

'name' => ['required', 'array', new TranslatableRequired('table_name', ['string', 'max:191'])]
```

---

## Roles Configuration (`config/roles.php`)

Defines role hierarchy and permissions.

```php
return [
    'class_paths' => [
        'role' => \App\Models\Role::class,
        'permission' => \App\Models\Permission::class,
    ],
    'default_guard' => 'sanctum',
    
    'roles' => [
        'default_role' => [
            'home' => ['report'],
            'type' => null,
            'permissions' => []
        ]
    ],
    
    'additional_operations' => [
        ['name' => 'Home', 'operations' => ['report']],
        ['name' => 'Log', 'operations' => ['read']],
    ],
];
```

---

## Report Configuration (`config/report.php`)

Defines report pages with cards and charts.

```php
return [
    'namespace' => 'App\\Tools\\Report',
    
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
            ],
        ],
    ],
];
```

---

## Standard Laravel Config Files

| File | Purpose |
|------|---------|
| `config/app.php` | App name, timezone, locale, providers |
| `config/auth.php` | Guards, providers, passwords |
| `config/database.php` | Database connections |
| `config/mail.php` | Mailer settings |
| `config/queue.php` | Queue drivers |
| `config/cache.php` | Cache stores |
| `config/filesystems.php` | Disk configurations |
| `config/reverb.php` | WebSocket server settings |
| `config/permission.php` | Spatie Permission settings |
| `config/activitylog.php` | Activity logging settings |

---

## Environment Variables

Key `.env` variables:

```env
# Application
APP_NAME=MyProject
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Africa/Cairo

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=starter_backend

# Authentication
AUTH_LOGIN_OTP=true
FRONT_SHARED_KEY=your_encryption_key

# LDAP (optional)
LDAP_ACTIVE=false
LDAP_TYPE=ad
LDAP_HOST=ldap.example.com

# Reverb WebSocket
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret

# Real-time
REALTIME_ENABLED=false
```

## See Also

- [Settings & Configuration](/guide/features/settings) — Runtime settings management
- [Installation](/guide/installation) — Initial setup
- [Authentication](/guide/authentication) — Auth configuration details