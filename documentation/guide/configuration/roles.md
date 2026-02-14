---
title: Roles & Permissions Configuration
description: Configure role definitions, permission inheritance, and access control
---

# Roles & Permissions Configuration

The `config/roles.php` file defines roles, permissions, and their inheritance rules. This works with Spatie Laravel Permission and the custom `RoleService`.

## Model Class Paths

```php
'class_paths' => [
    'role' => \App\Models\Central\Role::class,
    'permission' => \App\Models\Central\Permission::class,
],
```

These models extend Spatie's base models with additional functionality.

## Default Guard

```php
'default_guard' => 'sanctum',
```

All roles and permissions use the Sanctum guard by default.

## Role Definitions

```php
'roles' => [
    'default_role' => [
        'home' => ['report'],
        'type' => null,
        'permissions' => [],
    ],
    
    'manager' => [
        'like' => 'admin',           // Inherit from admin role
        'type' => 'exception',       // Remove specific permissions
        'permissions' => [
            'users' => ['delete'],   // Remove delete permission
        ],
    ],
    
    'editor' => [
        'like' => null,              // No inheritance
        'type' => 'added',           // Add specific permissions
        'permissions' => [
            'posts' => ['create', 'update'],
        ],
    ],
],
```

### Role Properties

| Property | Description |
|----------|-------------|
| `like` | Role to inherit permissions from |
| `type` | How to modify inherited permissions: `exception` (remove) or `added` (add) |
| `permissions` | Model-specific permissions to add or remove |

### Permission Inheritance

**No inheritance (`like: null`):**
```php
'editor' => [
    'like' => null,
    'permissions' => [
        'posts' => ['create', 'update'],
    ],
],
// Result: Only has create-post, update-post
```

**Full inheritance (`like: 'admin'`):**
```php
'manager' => [
    'like' => 'admin',
    'type' => null,  // No modifications
    'permissions' => [],
],
// Result: Same permissions as admin
```

**Exception (`type: 'exception'`):**
```php
'manager' => [
    'like' => 'admin',
    'type' => 'exception',
    'permissions' => [
        'users' => ['delete', 'force-delete'],
    ],
],
// Result: Admin permissions MINUS delete-user, force-delete-user
```

**Added (`type: 'added'`):**
```php
'support' => [
    'like' => 'viewer',
    'type' => 'added',
    'permissions' => [
        'tickets' => ['create', 'update'],
    ],
],
// Result: Viewer permissions PLUS create-ticket, update-ticket
```

## Model Operations

Models define their available operations:

```php
// In App\Models\Central\Admin
public bool $inPermission = true;
public array $basicOperations = ['create', 'update', 'delete'];
public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];
```

### Operation Types

| Type | Operations |
|------|------------|
| Basic | `create`, `update`, `delete` |
| Special | `view-all`, `view-own`, `restore`, `force-delete`, `toggle-active` |

### Permission Naming

Permissions follow the pattern: `{operation}-{model}`:

- `create-admin`
- `update-admin`
- `delete-admin`
- `view-all-admin`
- `restore-admin`

## Additional Operations

Configure extra operations for specific models:

```php
'additional_operations' => [
    'reports' => ['export', 'print'],
    'settings' => ['read', 'update'],
],
```

This adds permissions like `export-report`, `print-report`, etc.

## RoleService

The `RoleService` reads this configuration and creates roles/permissions:

```php
use App\Services\Global\RoleService;

$service = new RoleService();
$service->handle();  // Creates all roles and permissions
```

### Key Methods

```php
// Create roles with permissions
$service->createRole(['manager', 'editor']);

// Create permissions for a model
$service->createModelPermissions('User');

// Get all models that should have permissions
$models = $service->getModels();
```

## Usage in Controllers

Check permissions in controllers:

```php
Gate::authorize('view', User::class);
Gate::authorize('update', $user);
Gate::authorize('delete', $user);
```

## Usage in Policies

Policies check permissions:

```php
public function update(Admin $user, Admin $model): bool
{
    if (!$user->can('update-admin')) {
        return false;
    }
    return $this->ownsOrAll($user, $model);
}
```

## Seeding Roles

Roles are seeded in `Database\Seeders\Central\DatabaseSeeder`:

```php
public function run(): void
{
    $roleService = new RoleService();
    $roleService->handle();
    
    // Create admin user with roles
    $admin = Admin::create([...]);
    $admin->assignRole('admin');
}
```

## See Also

- [Authorization & Policies](/guide/authorization)
- [Role Service](/guide/features/role-service)
- [Permission Manager](/guide/tools/permission-manager)
