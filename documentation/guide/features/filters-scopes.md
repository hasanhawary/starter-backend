---
title: Filters & Query Scopes
description: Your project's query filters
---

# Filters & Query Scopes

Your project includes query filters in `app/Filters/Global/`.

## ActiveFilter

**File:** `app/Filters/Global/ActiveFilter.php`

Filters by `is_active` status.

**Your Code:**

```php
public function handle($request, Closure $next)
{
    $query = $next($request);

    $query->when(
        request()->has('is_active'),
        fn($query) => $query->where('is_active', (bool)request('is_active')),
    );

    return $query;
}
```

**Usage:**

```bash
GET /api/users?is_active=true
GET /api/users?is_active=false
```

---

## NameFilter

**File:** `app/Filters/Global/NameFilter.php`

Filters by name field.

**Your Code:**

```php
public function handle($request, Closure $next)
{
    $query = $next($request);

    when(request('search'), static fn() => $query->where('name', 'like', '%' . request('search') . '%'));

    return $query;
}
```

**Usage:**

```bash
GET /api/users?search=John
```

---

## OrderByFilter

**File:** `app/Filters/Global/OrderByFilter.php`

Sorts results by column and direction.

**Your Code:**

```php
public function handle($request, Closure $next)
{
    $query = $next($request);

    try {
        $model = $query->getModel();
        $table = $model->getTable();

        $sortColumn = $this->resolveSortColumn($table, request('sort_column', 'id'));
        $sortDirection = $this->resolveSortDirection(request('sort_direction'));

        return $query->orderBy($sortColumn, $sortDirection);

    } catch (QueryException|\Exception $e) {
        Log::error('OrderByFilter unexpected error: ' . $e->getMessage());
        return $query->orderBy('id', 'desc');
    }
}

protected function resolveSortColumn(string $table, ?string $requested): string
{
    try {
        if (!$requested) {
            return 'id';
        }

        // Handle JSON dot notation like name.en => name->en
        if (str_starts_with($requested, 'name') && Schema::hasColumn($table, 'name')) {
            $jsonKey = explode('.', $requested)[1] ?? null;
            if ($jsonKey) {
                return "name->$jsonKey";
            }
        }

        // Check if the column exists
        if (Schema::hasColumn($table, $requested)) {
            return $requested;
        }

        // Fallback for "name" — if "first_name" exists
        if ($requested === 'name' && Schema::hasColumn($table, 'first_name')) {
            return 'first_name';
        }

        return 'id';
    } catch (\Exception $e) {
        Log::warning('Failed to resolve sort column: ' . $e->getMessage());
        return 'id';
    }
}

protected function resolveSortDirection(?string $direction = null): string
{
    return $direction && in_array(strtolower($direction), ['asc', 'desc'])
        ? strtolower($direction)
        : 'desc';
}
```

**Usage:**

```bash
GET /api/users?sort_column=name&sort_direction=asc
GET /api/users?sort_column=created_at&sort_direction=desc
GET /api/users?sort_column=name.en&sort_direction=asc
```

---

## Using Filters

In your controllers:

```php
$users = User::query()
    ->pipe(new ActiveFilter())
    ->pipe(new NameFilter())
    ->pipe(new OrderByFilter())
    ->paginate();
```

