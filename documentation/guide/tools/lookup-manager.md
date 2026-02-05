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

- `HelpController` exposes two endpoints:
	- `/api/help-models` → `Lookup::getModels($request->all())`
	- `/api/help-enums` → `Lookup::getEnums($request->all())`
	- `/api/help-configs` → `Lookup::getConfigs($request->all())`
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
GET /api/help-models?models=User,Role,Permission
GET /api/help-enums?enums=Gender,UserStatus
GET /api/help-enums?configs=templates
```

Returned model payload includes table name, columns, types, relationships, fillable/hidden fields, and validation hints. Enum responses are simple key-value maps suitable for select inputs.

## Configuration and caching

- Publish config with `php artisan vendor:publish --provider="LookupManager\Providers\LookupManagerProvider"` to adjust allowed models/enums.
- Results are cached; clear with `php artisan lookup:cache-reset` (or `php artisan cache:clear`).

## Tips

- Keep validation strict on the allowed models/enums to avoid leaking internal structures.
- Pair with [Dynamic CLI](/guide/tools/dynamic-cli) so generated models automatically surface in lookups.
