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
    ->send(Product::query()->visibleTo()->withListingData())
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

- Scope traits live in `app/Scopes/{Domain}/` (or `Modules/X/app/Scopes/` for a module).
- Ownership scopes like `related()` belong in scope traits, not controllers.
- Use scopes to centralize repeated ownership and protection rules.
- Do not call `->get()` inside relationship or scope methods.

### Name the query shape; never build it in the controller

- A `baseQuery()`, `listQuery()` or `loadForResponse()` private helper in a controller is a scope that has not been written yet. Move it to the model's scope trait as `scopeWithListingData()`, and give a route-bound record the matching `loadDetailData()` loader — the `with`/`load` pairing Laravel itself uses. The action then reads:

```php
// listing
Showcase::query()->visibleTo()->withListingData()

// single record
new ShowcaseResource($showcase->loadDetailData())
```

- Resolve per-row flags in SQL inside that scope — `withCount('pinUsers')`, `withExists(['pinUsers as is_pinned' => ...])` — and share the constraint between the scope and its loader so the Resource reads the same key either way. A Resource must never run a query to fill a field.
- Keep the selection scopes (`visibleTo()`, `active()`, `published()`) separate from the representation scopes (`withListingData()`), and apply the selection one first so a filter can never widen access.

### No generic scope dispatch from request input

- **Do not wire `App\Trait\Global\HasDynamicScopes` (`applyScopesFilter()`, the `scopes[]`/`values[]` parameters, a `$scopeMap` on the model) into a resource controller.** Letting raw request input choose which model method runs makes the endpoint's real surface unreadable from the route, the controller or the tests, and turns every future scope into public API by accident.
- Give each narrowing its own named request parameter in the Pipeline filter, mapped to the scope it means:

```php
$query->when(request()->boolean('mine'), fn (Builder $q) => $q->ownedBy());
$query->when(request()->boolean('published'), fn (Builder $q) => $q->published());
$query->when(request()->filled('expiring_within'), fn (Builder $q) => $q->expiringWithin((int) request('expiring_within')));
```

- The trait stays in the repository for the lookup layer (`help-models?scopes[]=active`), which takes a scope name as data on purpose. That is a different contract from a resource listing; do not copy it into one.
- Every scope needs a named consumer — an action, a Policy, a filter, a lookup, a command. A scope reachable only through a generic dispatcher is dead code with an open door attached.

## Performance Rules

- Eager load relations with `with()` or default `$with` only when always needed.
- Use `whenLoaded()` in resources to avoid accidental lazy loading.
- Use `withCount()` for counts instead of loading whole relations.
- Select only needed columns where safe.
- Paginate all list endpoints; never return all records for a table endpoint.
- Use `chunk()`, `chunkById()`, `lazy()`, or `cursor()` for large datasets.
- Use indexes for filter/sort/search columns.
- Use the [service, cache, and queue guidance](services.md) for expensive settings queries and background work.
- Avoid queries in loops, resources, mail views, notifications, or templates unless explicitly preloaded.

## Advanced Queries

- Prefer `addSelect()` subqueries when only one related value is needed.
- Use conditional aggregates instead of multiple count queries when useful.
- Use `setRelation()` when an already-known parent relation prevents circular N+1 issues.
- Prefer simple indexed queries over one complex query when clearer and faster.
- Match compound indexes to common filter and sort order.
