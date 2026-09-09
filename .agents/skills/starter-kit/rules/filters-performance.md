# Filters, Scopes, And Performance

Use this rule when creating or modifying list endpoints, Pipeline filters, sorting, scopes, eager loading, caching, or performance-sensitive queries.

## Pipeline Filter Template

Pipeline filters receive the query from `$next($request)`, read request params, mutate the query, and return the query.

```php
class JsonNameFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);
        $search = request('search');

        // Use QueryHelper for JSON translation search instead of duplicating SQL.
        when($search, static fn () => QueryHelper::applyJsonSearch($query, 'name', $search));

        return $query;
    }
}
```

## Sort Filter Template

```php
class OrderByFilter
{
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
            Log::error('OrderByFilter unexpected error: '.$e->getMessage());

            return $query->orderBy('id', 'desc'); // Fallback to default sorting
        }
    }
}
```

## Controller Usage

```php
$query = app(Pipeline::class)
    ->send(Product::query()->with(['creator']))
    ->through([
        JsonNameFilter::class,
        ActiveFilter::class,
        TrashedFilter::class,
        OrderByFilter::class,
    ])
    ->thenReturn();

return successResponse(wrapPaginate($query, ProductResource::class));
```

## Common Filters

- `ActiveFilter`: `is_active`.
- `DateFilter`: `start`, `end`.
- `EmailFilter`: `email`.
- `NameFilter`: plain `search` over `name`.
- `JsonNameFilter`: `search` over JSON `name`.
- `JsonDisplayNameFilter`: `search` over JSON `display_name`.
- `PhoneFilter`: `phone`.
- `OrderByFilter`: `sort_column`, `sort_direction`.
- `TrashedFilter`: `is_trashed`.
- `UserFilter`: multi-field user search.
- `ActivityLogFilter`: activity log search and date filters.
- `KeyFilter`: setting key.
- `GroupFilter`: setting group.

Create a custom filter only when existing filters cannot express the query. One filter handles one concern.

## Scopes

- Scope traits live in `app/Scopes/{Domain}/`.
- Ownership scopes like `related()` belong in scope traits, not controllers.
- Use scopes to centralize repeated ownership and protection rules.
- Do not call `->get()` inside relationship or scope methods.

## Performance Rules

- Eager load relations with `with()` or default `$with` only when always needed.
- Use `whenLoaded()` in resources to avoid accidental lazy loading.
- Use `withCount()` for counts instead of loading whole relations.
- Select only needed columns where safe.
- Paginate all list endpoints; never return all records for a table endpoint.
- Use chunking/lazy iteration for large datasets.
- Use indexes for filter/sort/search columns.
- Cache expensive settings/config queries with brand-aware keys when following `SettingService` patterns.
- Use queues for exports, email, SMS, notifications, reports, and heavy work.
- Avoid queries in loops, resources, mail views, notifications, or templates unless explicitly preloaded.
