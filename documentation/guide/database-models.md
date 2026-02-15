---
title: Database Models
description: Core Eloquent models and their relationships
---

# Database Models

This guide documents all core Eloquent models in the dashboard backend, their relationships, and how to use them.

## User Model

**Location:** `app/Models/User.php`

The User model represents admin users in the system.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `name` | string | User full name |
| `email` | string | User email (unique) |
| `phone` | string | User phone number |
| `password` | string | Hashed password |
| `avatar` | string | Avatar file path |
| `is_active` | boolean | User active status |
| `email_verified_at` | timestamp | Email verification timestamp |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

### Relationships

```php
// Get user's roles
$user->roles(); // BelongsToMany

// Get user's permissions
$user->permissions(); // BelongsToMany

// Get user's personal access tokens
$user->tokens(); // HasMany

// Get user's activity logs
$user->activities(); // HasMany (through ActivityLog)
```

### Usage Examples

```php
use App\Models\User;

// Create a user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
    'phone' => '+1234567890',
]);

// Find user by email
$user = User::where('email', 'john@example.com')->first();

// Get user with roles and permissions
$user = User::with('roles', 'permissions')->find($id);

// Check if user has permission
if ($user->can('create-users')) {
    // User has permission
}

// Assign role to user
$user->assignRole('admin');

// Revoke role from user
$user->removeRole('admin');

// Get all active users
$activeUsers = User::where('is_active', true)->get();

// Soft delete user
$user->delete();

// Restore soft deleted user
$user->restore();

// Force delete user
$user->forceDelete();
```

### Scopes

```php
// Get only active users
User::active()->get();

// Get only inactive users
User::inactive()->get();

// Get only deleted users
User::onlyTrashed()->get();

// Get all users including deleted
User::withTrashed()->get();
```

---

## Role Model

**Location:** `app/Models/Role.php`

The Role model represents user roles with permissions.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `name` | json | Role name (translatable) |
| `guard_name` | string | Guard name (default: 'api') |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Relationships

```php
// Get role's permissions
$role->permissions(); // BelongsToMany

// Get users with this role
$role->users(); // BelongsToMany
```

### Usage Examples

```php
use App\Models\Role;

// Create a role
$role = Role::create([
    'name' => ['en' => 'Administrator', 'ar' => 'مسؤول'],
    'guard_name' => 'api',
]);

// Find role by name
$role = Role::where('name->en', 'Administrator')->first();

// Get role with permissions
$role = Role::with('permissions')->find($id);

// Assign permission to role
$role->givePermissionTo('create-users');

// Revoke permission from role
$role->revokePermissionTo('create-users');

// Get all permissions for role
$permissions = $role->permissions;

// Get all users with this role
$users = $role->users;
```

---

## Permission Model

**Location:** `app/Models/Permission.php`

The Permission model represents system permissions.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `name` | json | Permission name (translatable) |
| `guard_name` | string | Guard name (default: 'api') |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Relationships

```php
// Get roles with this permission
$permission->roles(); // BelongsToMany

// Get users with this permission
$permission->users(); // BelongsToMany
```

### Usage Examples

```php
use App\Models\Permission;

// Create a permission
$permission = Permission::create([
    'name' => ['en' => 'Create Users', 'ar' => 'إنشاء المستخدمين'],
    'guard_name' => 'api',
]);

// Find permission by name
$permission = Permission::where('name->en', 'Create Users')->first();

// Get all roles with this permission
$roles = $permission->roles;

// Get all users with this permission
$users = $permission->users;
```

---

## Setting Model

**Location:** `app/Models/Setting.php`

The Setting model stores application configuration.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `key` | string | Setting key |
| `group` | string | Setting group (dot notation) |
| `value` | json | Setting value |
| `type` | string | Value type (text, textarea, boolean, etc.) |
| `is_multi_lang` | boolean | Whether value is multi-language |
| `label` | string | Display label |
| `placeholder` | string | Input placeholder |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Usage Examples

```php
use App\Models\Setting;

// Create a setting
$setting = Setting::create([
    'key' => 'site_name',
    'group' => 'general',
    'value' => 'My Application',
    'type' => 'text',
    'is_multi_lang' => false,
    'label' => 'Site Name',
]);

// Find setting by key and group
$setting = Setting::where('key', 'site_name')
    ->where('group', 'general')
    ->first();

// Get all settings in a group
$generalSettings = Setting::where('group', 'general')->get();

// Update setting value
$setting->update(['value' => 'New Site Name']);

// Get setting through helper
$siteName = setting('general.site_name');
```

---

## Country Model

**Location:** `app/Models/Country.php`

The Country model stores country master data.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `name` | json | Country name (translatable) |
| `code` | string | ISO country code |
| `phone_code` | string | International phone code |
| `flag` | string | Flag image path |
| `is_active` | boolean | Active status |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

### Usage Examples

```php
use App\Models\Country;

// Create a country
$country = Country::create([
    'name' => ['en' => 'United States', 'ar' => 'الولايات المتحدة'],
    'code' => 'US',
    'phone_code' => '+1',
    'is_active' => true,
]);

// Find country by code
$country = Country::where('code', 'US')->first();

// Get all active countries
$countries = Country::where('is_active', true)->get();

// Get country with translations
$country = Country::find($id);
echo $country->name['en']; // United States
echo $country->name['ar']; // الولايات المتحدة
```

---

## Notification Model

**Location:** `app/Models/Notification.php`

The Notification model stores user notifications.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `user_id` | integer | User ID (foreign key) |
| `title` | string | Notification title |
| `message` | string | Notification message |
| `type` | string | Notification type |
| `data` | json | Additional data |
| `is_read` | boolean | Read status |
| `read_at` | timestamp | Read timestamp |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Relationships

```php
// Get notification's user
$notification->user(); // BelongsTo
```

### Usage Examples

```php
use App\Models\Notification;

// Create a notification
$notification = Notification::create([
    'user_id' => $user->id,
    'title' => 'Welcome',
    'message' => 'Welcome to our platform',
    'type' => 'info',
    'data' => ['action_url' => '/dashboard'],
]);

// Get user's unread notifications
$unread = Notification::where('user_id', $user->id)
    ->where('is_read', false)
    ->get();

// Mark notification as read
$notification->update(['is_read' => true, 'read_at' => now()]);

// Get user's notifications
$notifications = $user->notifications()->latest()->get();
```

---

## ActivityLog Model

**Location:** `app/Models/ActivityLog.php`

The ActivityLog model stores audit trail of all model changes.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `log_name` | string | Log name |
| `description` | string | Activity description |
| `subject_type` | string | Model class name |
| `subject_id` | integer | Model ID |
| `causer_type` | string | User model class |
| `causer_id` | integer | User ID |
| `properties` | json | Changed properties (old/new values) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Usage Examples

```php
use App\Models\ActivityLog;

// Get all activity logs
$logs = ActivityLog::latest()->get();

// Get activity for specific model
$logs = ActivityLog::where('subject_type', User::class)
    ->where('subject_id', $userId)
    ->get();

// Get activity by user
$logs = ActivityLog::where('causer_id', $userId)->get();

// Get activity with changes
foreach ($logs as $log) {
    echo $log->description; // e.g., "created"
    echo $log->properties['old']; // Old values
    echo $log->properties['attributes']; // New values
}
```

---

## PersonalAccessToken Model

**Location:** `app/Models/PersonalAccessToken.php`

The PersonalAccessToken model stores API tokens for users.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Primary key |
| `tokenable_type` | string | Model class name |
| `tokenable_id` | integer | Model ID |
| `name` | string | Token name |
| `token` | string | Hashed token |
| `abilities` | json | Token abilities |
| `last_used_at` | timestamp | Last used timestamp |
| `expires_at` | timestamp | Expiration timestamp |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Usage Examples

```php
use App\Models\PersonalAccessToken;

// Get user's tokens
$tokens = $user->tokens;

// Create a token
$token = $user->createToken('api-token', ['*']);

// Get token value
$tokenValue = $token->plainTextToken;

// Revoke token
$token->token->delete();

// Get all tokens for user
$tokens = PersonalAccessToken::where('tokenable_id', $user->id)->get();
```

---

## Model Relationships Diagram

```
User
├── roles (BelongsToMany)
├── permissions (BelongsToMany)
├── tokens (HasMany → PersonalAccessToken)
└── activities (HasMany → ActivityLog)

Role
├── permissions (BelongsToMany)
└── users (BelongsToMany)

Permission
├── roles (BelongsToMany)
└── users (BelongsToMany)

Setting
└── (No relationships)

Country
└── (No relationships)

Notification
└── user (BelongsTo)

ActivityLog
├── subject (MorphTo)
└── causer (MorphTo)

PersonalAccessToken
└── tokenable (MorphTo)
```

---

## See Also

- [Authentication](/guide/authentication) — User authentication
- [Authorization](/guide/authorization) — Role and permission management
- [Settings](/guide/features/settings) — Settings management
- [Activity Logging](/guide/features/activity-logging) — Audit trail
