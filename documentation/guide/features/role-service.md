---
title: Role Service
description: How `RoleService` builds roles and permissions
---

# Role Service

`RoleService` lives at `app/Services/Global/RoleService.php` and centralizes role creation and permission assignment.

## What it does

- Creates basic roles (`root`, `admin`) and any configured roles
- Generates model permissions (e.g. `create-users`, `read-users`) based on each model's `basicOperations` and `specialOperations`
- Supports inheriting permissions from another role using `like` and modifies them via `type` (`exception`, `added`)
- Reads `config('roles')` for role definitions and `config('roles.additional_operations')` for model-specific extra operations

## Key methods

- `handle()` — main entry point. Creates admin/root and syncs configured roles and permissions.
- `createRole(array $roles = [null], ?Collection $models = null)` — create role(s) and assign model permissions
- `createModelPermissions(string $modelName)` — create permissions for a model and return Permission models
- `getModels(...$exceptions)` — list of models (app `Models` + Modules) that should have permissions
- `prepareOperations(string $modelName)` — resolve operations for a model (basic, special, and additional_operations)
- `getModelOperationsMapping(string $modelName)` — returns permissions strings like `create-users`

## Configuration

Edit `config/roles.php` to control role definitions:

- `roles` — define named roles and permission rules
- `additional_operations` — add extra operation sets for specific models
- `default.permissions` — permissions assigned to all roles by default

Example role in `config/roles.php`

```php
'roles' => [
    'manager' => [
        'like' => 'admin',
        'type' => 'exception', // remove specific permissions from admin when applicable
        'permissions' => [
            'users' => ['read', 'update'],
        ],
    ],
],
```

## Usage

- The service is usually invoked during install/setup routines:

```php
$service = new \App\Services\Global\RoleService();
$service->handle();
```

- You can create or sync roles programmatically by calling `createRole()` with a set of models.

### Notes

- Model classes can customize permissions by defining `basicOperations` and `specialOperations` properties.
- Modules are scanned under the `Modules/` directory; module models are included in permission generation.

## See also

- [API Reference](/guide/api-reference) — see endpoints list