---
title: Filters & Scopes
description: Query filters using Laravel Pipeline and Eloquent scopes
---

# Filters & Scopes

This page documents the request filters and query scopes used throughout the API. Filters transform Eloquent queries based on request parameters using Laravel's Pipeline pattern.

## How Filters Work

Controllers pipe queries through filter classes using Laravel's Pipeline:

```php
use Illuminate\Pipeline\Pipeline;

public function index(PageRequest $request): JsonResponse
{
    Gate::authorize('view', User::class);

    $query = app(Pipeline::class)
        ->send(User::with('roles')->related())
        ->through([
            UserFilter::class,
            ActiveFilter::class,
            TrashedFilter::class,
            OrderByFilter::class
        ])
        ->thenReturn();

    return successResponse(fetchData($query, $request->pageSize, UserResource::class));
}
```

## Filter Class Structure

Each filter implements a `handle` method that receives the query and passes it to the next filter:

```php
<?php

namespace App\Filters\Global;

use Closure;

class ActiveFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(
            request()->has('is_active'),
            fn($query) => $query->where('is_active', (bool)request('is_active')),
        );

        return $query;
    }
}
```

::: tip
Filters receive the query from `$next($request)` and apply conditions using `when()` for clean, conditional logic.
:::


## Built-in Global Filters

Located in `app/Filters/Global/`:

### ActiveFilter

Filters by `is_active` boolean column.

```php
// Query param: is_active (0 or 1)
GET /api/users?is_active=1
```

### TrashedFilter

Shows only soft-deleted records.

```php
// Query param: is_trashed (boolean)
GET /api/users?is_trashed=1

// Implementation
$query->when(request('is_trashed', false), fn($q) => $q->onlyTrashed());
```

### DateFilter

Filters by `created_at` date range.

```php
// Query params: start, end (Y-m-d format)
GET /api/users?start=2024-01-01&end=2024-12-31

// Implementation
if (!empty(request('start'))) {
    $query->whereDate('created_at', '>=', Carbon::parse(request('start'))->format('Y-m-d'));
}
if (!empty(request('end'))) {
    $query->whereDate('created_at', '<=', Carbon::parse(request('end'))->format('Y-m-d'));
}
```

### OrderByFilter

Handles sorting with smart column resolution.

```php
// Query params: sortColumn, sortDirection
GET /api/users?sortColumn=name&sortDirection=asc

// Features:
// - JSON field support: name.en becomes name->en
// - Fallback to 'id' if column doesn't exist
// - Handles 'name' -> 'first_name' fallback
```

### NameFilter

Searches by `name` column.

```php
// Query param: search
GET /api/users?search=john

// Implementation
$query->where('name', 'like', '%' . request('search') . '%');
```

### EmailFilter

Searches by `email` column.

```php
GET /api/users?search=john@example.com
```

### PhoneFilter

Searches by `phone` column.

```php
GET /api/users?search=1234567890
```

### JsonNameFilter

Searches inside JSON `name` column for translatable fields.

```php
// Searches name->en and name->ar
GET /api/countries?search=egypt
```

### JsonDisplayNameFilter

Searches inside JSON `display_name` column.

```php
GET /api/permissions?search=create
```

## Module-Specific Filters

### UserFilter

Combined search across multiple fields.

```php
// app/Filters/User/UserFilter.php
$query->when(request()->has('search') && !empty(request('search')), function ($query) {
    $query->where(function ($query) {
        $query->where('name', 'like', '%' . request('search') . '%')
            ->orWhere('email', 'like', '%' . request('search') . '%')
            ->orWhere('phone', 'like', '%' . request('search') . '%');
    });
});
```

### ActivityLogFilter

Comprehensive filter for activity logs.

```php
// Query params: search, model, user_id, operation, date_from, date_to
GET /api/activity-logs?model=User&operation=updated&date_from=2024-01-01
```

### SettingFilter (GroupFilter, KeyFilter)

Filter settings by group or key.

```php
GET /api/settings?group=general
GET /api/settings?key=app_name
```

## Creating Custom Filters

1. Create a new filter class in `app/Filters/YourModule/`:

```php
<?php

namespace App\Filters\Post;

use Closure;

class PostFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        // Search by title
        $query->when(request()->filled('search'), function ($query) {
            $query->where('title', 'like', '%' . request('search') . '%');
        });

        // Filter by category
        $query->when(request()->filled('category_id'), function ($query) {
            $query->where('category_id', request('category_id'));
        });

        // Filter by status
        $query->when(request()->filled('status'), function ($query) {
            $query->where('status', request('status'));
        });

        return $query;
    }
}
```

2. Include in controller pipeline:

```php
$query = app(Pipeline::class)
    ->send(Post::with('category'))
    ->through([
        PostFilter::class,
        ActiveFilter::class,
        DateFilter::class,
        TrashedFilter::class,
        OrderByFilter::class
    ])
    ->thenReturn();
```

## Eloquent Scopes

Located in `app/Scopes/{Module}/`, scopes are reusable query constraints defined as traits.

### UserScopes

```php
// app/Scopes/User/UserScopes.php
trait UserScopes
{
    // Filter by ownership (view-own vs view-all permission)
    public function scopeRelated(Builder $builder): void
    {
        $builder->when(!auth()->user()->can('view-all-user'), function ($subQuery) {
            $subQuery->where('created_by', auth()->id());
        });
    }

    // Exclude current user from results
    public function scopeExcludeLoggedInUser(Builder $query): Builder
    {
        return $query->where('id', '!=', auth()->id());
    }

    // Exclude root users
    public function scopeExcludeRoot(Builder $query): Builder
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', '!=', 'root');
        });
    }

    // Filter by role
    public function scopeWithRole(Builder $query, ?string $role = null): Builder
    {
        return $query->when($role, function ($subQuery) use ($role) {
            $subQuery->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        });
    }
}
```

**Usage in models:**

```php
class User extends Authenticatable
{
    use UserScopes;
}

// In controllers
User::related()->get();                    // Respects view-own/view-all
User::excludeLoggedInUser()->get();        // Exclude current user
User::excludeRoot()->get();                // Exclude root users
User::withRole('admin')->get();            // Filter by role
```

## Common Query Patterns

### Combining Filters with Eager Loading

```php
$query = app(Pipeline::class)
    ->send(User::with(['roles', 'phoneCode', 'creator'])->related())
    ->through([UserFilter::class, ActiveFilter::class, OrderByFilter::class])
    ->thenReturn();
```

### Conditional Eager Loading in Resources

```php
// In UserResource
'roles' => $this->whenLoaded('roles', fn() => BasicResource::collection($this->roles), []),
'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator)),
```

## API Examples

```bash
# List active users with search and ordering
GET /api/users?is_active=1&search=john&sortColumn=name&sortDirection=asc

# Get trashed items
GET /api/users?is_trashed=1

# Date range filter
GET /api/activity-logs?start=2024-01-01&end=2024-01-31

# Combined filters
GET /api/users?is_active=1&search=admin&sortColumn=created_at&sortDirection=desc&pageSize=20
```

## See Also

- [Architecture](/guide/architecture) — Pipeline pattern overview
- [API Reference](/guide/api-reference) — Endpoint documentation
- [Dynamic CLI](/guide/tools/dynamic-cli) — Auto-generated filters
