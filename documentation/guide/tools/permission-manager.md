---
title: Permission Manager
description: Role-based access control with CRUD operations
---

# Permission Manager

A simple but powerful role & permission manager for Laravel, built on top of ***spatie/laravel-permission***.

- For more information, visit [Permission Manager on Packagist](https://packagist.org/packages/hasanhawary/permission-manager).

### Features
- One-line setup: Access::handle() builds roles & permissions automatically.
- Ships with default roles (root, admin).
- Auto-discovers your models and generates permissions (create-user, update-project, etc.).
- Config-driven roles: inheritance (like), add/remove (added, exception), and custom permission sets.
- Additional operations for global actions not tied to models.
- Translation-ready: multilingual display_name for roles & permissions (e.g., English & Arabic).
- Works with Laravel Modules as well as app/Models.

## Installation

Already bundled; update with:

```bash
composer require hasanhawary/permission-manager
```

## How it works in this project

- **Policies and gates**: Controllers consistently call `Gate::authorize` before mutating resources (for example, user CRUD in `UserController`).
- **Permission middleware**: `SettingController` registers middleware via `Spatie\Permission\Middleware\PermissionMiddleware` to guard read/update endpoints (`read-setting`, `update-setting`).
- **Roles on models**: `User` uses `HasRoles`; role and permission syncing is centralized in `UserController::syncRelations`.

Examples:

```php
// app/Http/Controllers/API/User/UserController.php
public function index(PageRequest $request): JsonResponse
{
	Gate::authorize('view', User::class);
	// ...
}

// app/Http/Controllers/API/Global/Setting/SettingController.php
public static function middleware(): array
{
	return [
		new Middleware(PermissionMiddleware::using('read-setting'), only: ['index']),
		new Middleware(PermissionMiddleware::using('update-setting'), only: ['setConfigForUser']),
	];
}
```

## Typical flows

- **User management**: create/update/delete calls go through policies, and roles/permissions are synced from the request payload (`roles`, `permissions`).
- **Route protection**: attach `permission` middleware in routes or controller middleware definitions to require a specific permission name.
- **Inline checks**: call `$user->hasRole('admin')` or `$user->hasPermissionTo('create_users')` inside domain services when needed.

## Configuration

Publish Spatie’s config if you need to customize table names or cache:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Key options live in `config/permission.php` (guards, cache store, column names).

## Tips

- Keep permission strings consistent across policies and middleware to avoid drift.
- Prefer policies for resource checks and middleware for coarse-grained route access.
- When seeding, create roles first and attach permissions so controllers can rely on `Gate::authorize` without extra conditionals.
