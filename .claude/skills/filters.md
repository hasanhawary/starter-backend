# Skill: Filters

## How Filters Work

The `Pipeline` sends the **Eloquent Builder** (not the request) through each filter class.

```php
$query = app(Pipeline::class)
    ->send(Xxx::query())           // ← Builder is the payload
    ->through([
        XxxFilter::class,
        ActiveFilter::class,
        TrashedFilter::class,
        OrderByFilter::class,
    ])
    ->thenReturn();
```

Each filter receives the builder as the first arg, calls `$next($query)` first to let the chain continue, then applies its own logic:

```php
public function handle($query, Closure $next)
{
    $query = $next($query);       // ← call next BEFORE applying your filter
    $query->when(request('key'), fn($q) => $q->where('column', request('key')));
    return $query;
}
```

---

## Existing Global Filters (Reuse First)

| Filter | Request param | Behavior |
|---|---|---|
| `ActiveFilter` | `is_active` (0/1) | `->where('is_active', bool)` |
| `TrashedFilter` | `trashed` (1) | `->onlyTrashed()` |
| `OrderByFilter` | `order_by`, `order_dir` | `->orderBy(col, dir)` |
| `DateFilter` | `date_from`, `date_to` | `->whereBetween('created_at', ...)` |
| `NameFilter` | `name` | `->where('name', 'like', ...)` |
| `EmailFilter` | `email` | `->where('email', 'like', ...)` |
| `PhoneFilter` | `phone` | `->where('phone', 'like', ...)` |
| `JsonNameFilter` | `name` | JSON column name search (translatable) |
| `JsonDisplayNameFilter` | `display_name` | JSON column display_name search |

---

## Writing a New Filter

**Simple scalar filter:**
```php
namespace App\Filters\Admin\{Domain};

use Closure;

class StatusFilter
{
    public function handle($query, Closure $next)
    {
        $query = $next($query);

        $query->when(
            request()->has('status') && request('status') !== null,
            fn($q) => $q->where('status', request('status'))
        );

        return $query;
    }
}
```

**Search across multiple columns:**
```php
$query->when(request('search'), function ($q) {
    $q->where(function ($q) {
        $q->where('name', 'like', '%' . request('search') . '%')
          ->orWhere('email', 'like', '%' . request('search') . '%');
    });
});
```

**Translatable JSON column search:**
```php
$query->when(request('name'), function ($q) {
    $q->where(function ($q) {
        $q->whereJsonContains('name->en', request('name'))
          ->orWhereJsonContains('name->ar', request('name'));
    });
});
```

**Relation filter:**
```php
$query->when(request('category_id'), fn($q) => $q->where('category_id', request('category_id')));
```

---

## Placement Rules

- Global / reusable → `app/Filters/Global/`
- Admin-side resource-specific → `app/Filters/Admin/{Domain}/`
- User-side resource-specific → `app/Filters/Landing/{Domain}/`
- One class per filter — never combine two concerns into one filter
