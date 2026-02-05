---
title: Filters & Scopes
description: List of built-in request filters and how to use them
---

## Filters & Scopes

This page documents the request filters used throughout the API. Filters are used via the Laravel Pipeline (see controllers) and transform or constrain Eloquent queries based on request parameters.

### How filters are applied

- Controllers typically build a query and pipe it through filters using the Pipeline:

```php
$query = app(Pipeline::class)
    ->send(User::with('roles')->related())
    ->through([UserFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
    ->thenReturn();
```

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
