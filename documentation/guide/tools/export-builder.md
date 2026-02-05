---
title: Export Builder
description: Generate large data exports asynchronously
---

# Export Builder

Lightweight, framework-friendly export generation for Laravel powered by maatwebsite/excel. Define tiny export classes and trigger CSV/XLS/XLSX downloads with built-in filtering, relations, and smart value formatting.

* For more information, visit [Export Builder on Packagist](https://packagist.org/packages/hasanhawary/export-builder)

### Why Export Builder?
- Zero boilerplate: focus on a simple config array, not Excel internals.
- Convention over configuration: resolves exports by page name and namespace.
- Powerful mapping: convert types (date, datetime, int, money, booleans), translate headings, and resolve enums.
- Relations aware: eager-load one/many relations, flatten nested data, count/list/concat children.
- Production-ready: safe file names, error logging, and HTTP responses that Just Work.

## Installation

The package ships with the starter (see `packages/export-builder/`). To pull updates:

```bash
composer require hasanhawary/export-builder
```

## How it works in this project

- Requests hit `GET /api/admin/export` and are validated by `ExportRequest`.
- **ExportController** builds a filter payload, injects defaults (`page` fallback to `user`, `related_type` set to `count`), then hands everything to `ExportBuilder`.
- `ExportBuilder` looks up the matching export profile under `App\Tools\Export` and streams the file (queued or synchronous based on config).

Controller entrypoint:

```php
// app/Http/Controllers/API/Global/Export/ExportController.php
public function __invoke(ExportRequest $request): BinaryFileResponse
{
	return (new ExportBuilder($this->filters($request)))->response();
}
```

## Define export profiles

Exports extend `HasanHawary\ExportBuilder\BaseExport`. Columns, relations, and value casting are declared in the constructor. Example from `App\Tools\Export\UserExport`:

```php
public function __construct(public array $filter)
{
	$config = [
		'model' => User::class,
		'columns' => [
			'id' => 'int',
			'name' => 'text',
			'email' => 'text',
			'phone' => 'text',
			'gender' => UserGenderEnum::class,
			'is_active' => 'boolean',
			'last_login' => 'datetime',
			'created_at' => 'datetime',
		],
		'relations' => [
			'one' => [
				'created_by' => ['creator' => ['name' => 'text', 'id' => 'int']],
			],
			'many' => [
				'count' => [],
				'list' => [],
				'concat' => ['roles' => ['display_name' => 'text']],
			],
		],
	];

	parent::__construct($config, $filter);
}
```

## Typical API flow

```bash
GET /api/admin/export?page=user&format=excel&columns[]=id&columns[]=name&columns[]=email&is_active=1
```

**Query Parameters:**
| Parameter | Description |
|-----------|-------------|
| `page` | Export profile name (e.g., `user`, `country`) |
| `format` | Output format: `excel`, `csv`, or `pdf` |
| `columns[]` | Array of columns to include |
| `*` | Any filter parameters (passed to query) |

The response streams the file directly for download.

## Queue and storage

- Queue name defaults to `exports`; run `php artisan queue:work --queue=exports` for background processing.
- File location, chunk size, and URL expiry live in `config/export-builder.php` (publish stubs with `php artisan vendor:publish --provider="ExportBuilder\Providers\ExportBuilderProvider"`).

## Tips

- Keep export profiles thin: move computed columns into model accessors or database expressions to avoid memory pressure.
- Prefer minimal column lists from the client; wide exports increase processing time.
- Re-use `related_type` when you only need counts to cut down eager loading.
