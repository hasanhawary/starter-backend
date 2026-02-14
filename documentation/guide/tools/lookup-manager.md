---
title: Lookup Manager
description: Fetch model metadata and enums dynamically
---

# Lookup Manager

A lightweight, framework-friendly Laravel package for dynamic model lookups, enum discovery, and translation-aware enum lists and return configuration arrays.

- For more information, visit [Lookup Manager on Packagist](https://packagist.org/packages/hasanhawary/lookup-manager).

## Installation

Already included; update if needed:

```bash
composer require hasanhawary/lookup-manager
```

## How it works in this project

- `HelpController` exposes three endpoints (available in both admin and landing routes):
	- `GET /api/admin/help-models` → `Lookup::getModels($request->all())`
	- `GET /api/admin/help-enums` → `Lookup::getEnums($request->all())`
	- `GET /api/admin/help-configs` → `Lookup::getConfigs($request->all())`
	- `GET /api/help-models` → Landing equivalent (unauthenticated)
	- `GET /api/help-enums` → Landing equivalent (unauthenticated)
	- `GET /api/help-configs` → Landing equivalent (unauthenticated)
- Requests are validated by `HelpModelRequest`, `HelpEnumRequest` and `HelpConfigRequest` to ensure only allowed models/enums/configs are queried.
- Responses are wrapped with `successResponse`, matching the API contract used across controllers.

Controller snippets:

```php
// app/Http/Controllers/API/Global/Help/HelpController.php
public function models(HelpModelRequest $request): JsonResponse
{
	return successResponse(Lookup::getModels($request->all()));
}

public function enums(HelpEnumRequest $request): JsonResponse
{
	return successResponse(Lookup::getEnums($request->all()));
}

public function configs(HelpConfigRequest $request): JsonResponse
{
	return successResponse(Lookup::getConfigs($request->all()));
}
```

## Example requests

```bash
# Admin routes (authenticated)
GET /api/admin/help-models?models=User,Role,Permission
GET /api/admin/help-enums?enums=Gender,UserStatus
GET /api/admin/help-configs?configs=templates

# Landing routes (public)
GET /api/help-models?models=User,Role
GET /api/help-enums?enums=Gender
GET /api/help-configs?configs=templates
```

Returned model payload includes table name, columns, types, relationships, fillable/hidden fields, and validation hints. Enum responses are simple key-value maps suitable for select inputs.

## Configuration and caching

- Publish config with `php artisan vendor:publish --provider="LookupManager\Providers\LookupManagerProvider"` to adjust allowed models/enums.
- Results are cached; clear with `php artisan lookup:cache-reset` (or `php artisan cache:clear`).

## Tips

- Keep validation strict on the allowed models/enums to avoid leaking internal structures.
- Pair with [Dynamic CLI](/guide/tools/dynamic-cli) so generated models automatically surface in lookups.
