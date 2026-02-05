---
title: Filters & Scopes
description: Pipeline-based query filters and Eloquent scopes
---

# Filters & Scopes

This page documents the request filters used throughout the API. Filters use Laravel's Pipeline pattern to transform Eloquent queries based on request parameters.

## How Filters Work

Controllers pipe queries through filter classes:

```php
$query = app(Pipeline::class)
    ->send(User::query())
    ->through([
        UserFilter::class,
        ActiveFilter::class,
        TrashedFilter::class,
        OrderByFilter::class
    ])
    ->thenReturn();

return successResponse(fetchData($query, $request->pageSize, UserResource::class));
```

Each filter has a `handle($request, Closure $next)` method that modifies the query.

---

## Global Filters (`app/Filters/Global/`)

### ActiveFilter

Filters by `is_active` boolean.

```php
// Query: ?is_active=1 or ?is_active=0
$query->when(request()->has('is_active'), fn($q) => 
    $q->where('is_active', (bool)request('is_active'))
);
```

### DateFilter

Filters by `created_at` date range.

```php
// Query: ?start=2024-01-01&end=2024-12-31
if (!empty(request('start'))) {
    $query->whereDate('created_at', '>=', request('start'));
}
if (!empty(request('end'))) {
    $query->whereDate('created_at', '<=', request('end'));
}
```

### TrashedFilter

Shows only soft-deleted records.

```php
// Query: ?is_trashed=1
$query->when(request('is_trashed'), fn($q) => $q->onlyTrashed());
```

### OrderByFilter

Smart ordering with JSON field support and fallbacks.

```php
// Query: ?sortColumn=name.en&sortDirection=asc
// Converts name.en to name->en for JSON columns
// Falls back to 'id' desc if column doesn't exist
```

**Supports:**
- Regular columns: `?sortColumn=email`
- JSON paths: `?sortColumn=name.en` → `ORDER BY name->en`
- Fallback to `first_name` if `name` requested but doesn't exist

### JsonNameFilter

Searches inside JSON `name` column across all languages.

```php
// Query: ?search=مصر
// Searches name->ar, name->en, etc.
QueryHelper::applyJsonSearch($query, 'name', $search);
```

### JsonDisplayNameFilter

Same as JsonNameFilter but for `display_name` column.

### NameFilter / EmailFilter / PhoneFilter

Simple LIKE searches on respective columns.

```php
// NameFilter
$query->where('name', 'like', '%' . request('search') . '%');
```

---

## Admin Filters (`app/Filters/Admin/`)

### UserFilter

Combined search across multiple fields:

```php
$query->where(function ($q) {
    $q->where('name', 'like', '%' . request('search') . '%')
      ->orWhere('email', 'like', '%' . request('search') . '%')
      ->orWhere('phone', 'like', '%' . request('search') . '%');
});
```

---

## Setting Filters (`app/Filters/Setting/`)

### GroupFilter

Filter settings by group.

### KeyFilter

Filter settings by key.

---

## Creating Custom Filters

1. Create class in `app/Filters/`:

```php
<?php

namespace App\Filters\Custom;

use Closure;

class MyFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request('my_param'), function ($q) {
            $q->where('column', request('my_param'));
        });

        return $query;
    }
}
```

2. Add to controller pipeline:

```php
->through([MyFilter::class, OrderByFilter::class])
```

---

## Common Query Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `search` | Free-text search | `?search=john` |
| `is_active` | Filter by active status | `?is_active=1` |
| `is_trashed` | Show soft-deleted only | `?is_trashed=1` |
| `sortColumn` | Column to sort by | `?sortColumn=name` |
| `sortDirection` | Sort order (asc/desc) | `?sortDirection=desc` |
| `start` | Date range start | `?start=2024-01-01` |
| `end` | Date range end | `?end=2024-12-31` |
| `pageSize` | Items per page | `?pageSize=20` |

## Examples

```bash
# Active users named "john", sorted by name
GET /api/admin/users?is_active=1&search=john&sortColumn=name&sortDirection=asc

# Deleted users
GET /api/admin/users?is_trashed=1

# Users created in January 2024
GET /api/admin/users?start=2024-01-01&end=2024-01-31

# Countries sorted by Arabic name
GET /api/admin/countries?sortColumn=name.ar&sortDirection=asc
```

## See Also

- [API Reference](/guide/api-reference) — All endpoints
- [Architecture](/guide/architecture) — Pipeline pattern

### Common query params

- `search` — free-text search used by many filters
- `is_active` — filter active/inactive records
- `trashed` — include soft-deleted records
- `sortColumn` / `sortDirection` — ordering
- date range params like `start` / `end`

### Built-in filters

| Filter | Query param(s) | Description |
|--------|----------------|-------------|
| `ActiveFilter` | `is_active` | Filters by `is_active` boolean (1/0) |
| `DateFilter` | `start`, `end` | Filter by `created_at` date range |
| `NameFilter` | `search` | `where name like %search%` |
| `EmailFilter` | `search` | `where email like %search%` |
| `PhoneFilter` | `search` | `where phone like %search%` |
| `JsonNameFilter` | `search` | Search inside JSON `name` column (`name->en`, etc.) |
| `JsonDisplayNameFilter` | `search` | Search inside JSON `display_name` column |
| `OrderByFilter` | `sortColumn`, `sortDirection` | Handles smart ordering (JSON keys, fallbacks) |
| `TrashedFilter` | `trashed` | Use `onlyTrashed()` when truthy |
| `UserFilter` | `search` | Searches `name`, `email`, and `phone` in one filter |
| `ActivityLogFilter` | `search`, `model`, `user_id`, `operation`, `date_from`, `date_to` | Filters activity logs |
| `GroupFilter` / `KeyFilter` (Setting) | `group`, `key` | Filter settings by group or key |

## Examples

- List active users with search and ordering:

```
GET /api/users?is_active=1&search=john&sortColumn=name&sortDirection=asc
```

- Get trashed items:

```
GET /api/users?trashed=1
```

### Notes & Tips

- `OrderByFilter` contains logic for JSON `name` fields and falls back to sensible defaults.
- Filters rely on request helper functions; ensure parameters are present and correctly named.
- To add a new filter: create a class under `app/Filters` with a `handle($request, Closure $next)` method and include it in controller pipelines.

## See also

- [API Reference](/guide/api-reference) — see endpoints list
