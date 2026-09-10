<?php

namespace App\Filters\Global;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

class OrderByFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        try {
            $model = $query->getModel();
            $table = $model->getTable();

            $requested = request('sort_column', 'id');
            $sortDirection = $this->resolveSortDirection(request('sort_direction'));

            // Listings also show values no column holds (`remaining_days` is
            // derived from `deadline` by the resource); those sort by the SQL
            // expression the model declares them as.
            if ($expression = $this->resolveSortExpression($model, $requested)) {
                return $query->orderByRaw($expression.' '.$sortDirection);
            }

            // Listings show a whole related record in one column (`creator`,
            // `manager`), so sorting by it means sorting by the name it displays.
            if (! str_contains($requested, '.')
                && ! Schema::hasColumn($table, $requested)
                && $this->resolveSortRelation($model, $requested)) {
                $requested .= '.name';
            }

            // Dot notation sorts by a related model's column, e.g. `creator.name`.
            // Only single, non-morph relations (BelongsTo/HasOne) are supported.
            if (str_contains($requested, '.') && ! str_starts_with($requested, 'name')) {
                if ($sorted = $this->applyRelatedColumnOrder($query, $model, $requested, $sortDirection)) {
                    return $sorted;
                }

                $requested = 'id';
            }

            $sortColumn = $this->resolveSortColumn($model, $requested);

            // String-backed enum columns store a raw value (e.g. `appeal`) but
            // display a translated label; ordering by the raw value ignores the
            // translation, so sort by the label instead.
            if ($enumClass = $this->resolveSortEnum($model, $sortColumn)) {
                return $this->applyEnumOrder($query, $enumClass, $sortColumn, $sortDirection);
            }

            return $this->applyOrder($query, $sortColumn, $sortDirection);
        } catch (QueryException|\Exception $e) {
            Log::error('OrderByFilter unexpected error: '.$e->getMessage());

            return $query->orderBy('id', 'desc');
        }
    }

    /**
     * Order by a `relation.column` sort key (e.g. `creator.name`) using a
     * correlated subquery against the related table. Restricted to single,
     * non-morph relations (BelongsTo/HasOne/HasOneThrough); returns null when
     * the relation or column cannot be resolved so the caller can fall back to
     * a plain column.
     */
    protected function applyRelatedColumnOrder(Builder $query, Model $model, string $requested, string $direction): ?Builder
    {
        [$relationName, $column] = explode('.', $requested, 2);

        if (str_contains($column, '.') || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return null;
        }

        $relation = $this->resolveSortRelation($model, $relationName);

        if ($relation === null) {
            return null;
        }

        if ($relation instanceof HasOneThrough) {
            return $this->applyThroughColumnOrder($query, $model, $relation, $column, $direction);
        }

        $related = $relation->getRelated();

        // The subquery reads the related table under an alias: a self
        // referencing relation (`parent` on the same table) would otherwise
        // compare that table against itself instead of against the outer row,
        // and return the same value for every record.
        $alias = $related->getTable().'_sort';
        $expression = $this->relatedColumnExpression($related, $column, $alias);

        if ($expression === null) {
            return null;
        }

        $sub = $related->newInstance()
            ->setTable($alias)
            ->newQuery()
            ->from($related->getTable().' as '.$alias)
            ->selectRaw($expression)
            ->limit(1);

        // The related side of the correlation is the alias; the outer side keeps
        // the table the listing itself is querying.
        if ($relation instanceof BelongsTo) {
            $sub->whereColumn($alias.'.'.$relation->getOwnerKeyName(), $relation->getQualifiedForeignKeyName());
        } else {
            $sub->whereColumn($alias.'.'.$relation->getForeignKeyName(), $relation->getQualifiedParentKeyName());
        }

        return $query->orderBy($sub, $direction);
    }

    /**
     * Order by a column on a `HasOneThrough` relation (e.g. `main_user.name`),
     * whose row is only reachable across a pivot table. The relation's own
     * query already carries that join and the constraints that single out the
     * right row (`type`, `is_active`), so it is reused as the correlated
     * subquery and only the link back to the outer row is added — the relation
     * is built without constraints (see resolveSortRelation()), so it does not
     * carry the empty parent key of the model the listing was resolved from.
     */
    protected function applyThroughColumnOrder(Builder $query, Model $model, HasOneThrough $relation, string $column, string $direction): ?Builder
    {
        $related = $relation->getRelated();

        // The subquery reads the related and pivot tables under their own
        // names, so neither may be the table the listing itself is querying.
        if (in_array($model->getTable(), [$related->getTable(), $relation->getParent()->getTable()], true)) {
            return null;
        }

        $expression = $this->relatedColumnExpression($related, $column);

        if ($expression === null) {
            return null;
        }

        $sub = $relation->getQuery()
            ->whereColumn($relation->getQualifiedFirstKeyName(), $relation->getQualifiedLocalKeyName())
            ->selectRaw($expression)
            ->limit(1);

        return $query->orderBy($sub, $direction);
    }

    /**
     * Resolve the relation a sort key points at, or null when it points at
     * anything else. Only single, non-morph relations (BelongsTo/HasOne/
     * HasOneThrough) can be sorted by, and a relation is recognised from its
     * declared return type, so a crafted sort key can never reach an unrelated
     * model method.
     *
     * The relation is built without constraints: `$model` is the blank instance
     * the listing query was resolved from, so the parent key a constrained
     * relation would filter on is null, which would leave the subquery matching
     * no row at all.
     */
    protected function resolveSortRelation(Model $model, string $name): ?Relation
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            return null;
        }

        // A listing names its columns as the response does (`contract_type`),
        // while the relation behind them is camel cased (`contractType`).
        $method = method_exists($model, $name) ? $name : Str::camel($name);

        if (! method_exists($model, $method)) {
            return null;
        }

        try {
            $returnType = (new ReflectionMethod($model, $method))->getReturnType();

            if (! $returnType instanceof ReflectionNamedType || ! is_subclass_of($returnType->getName(), Relation::class)) {
                return null;
            }

            $relation = Relation::noConstraints(fn () => $model->{$method}());
        } catch (\Throwable) {
            return null;
        }

        if ($relation instanceof MorphTo
            || ! ($relation instanceof BelongsTo || $relation instanceof HasOne || $relation instanceof HasOneThrough)) {
            return null;
        }

        return $relation;
    }

    /**
     * Build the SQL expression to sort a related table by, qualified with the
     * alias the subquery reads that table under (its own name by default). A `name` (or
     * `translation_name`) request resolves to the localized JSON value for
     * translatable models, a full-name concat for person tables, or the plain
     * `name` column otherwise. Any other request must be a real column.
     */
    protected function relatedColumnExpression(Model $related, string $column, ?string $alias = null): ?string
    {
        $table = $related->getTable();
        $alias ??= $table;

        if (in_array($column, ['name', 'translation_name'], true)) {
            if (in_array('name', $this->translatableAttributes($related), true)) {
                $locale = app()->getLocale();

                return "LOWER(JSON_UNQUOTE(JSON_EXTRACT(`$alias`.`name`, '$.$locale')))";
            }

            if (Schema::hasColumn($table, 'name')) {
                return "`$alias`.`name`";
            }

            if (Schema::hasColumn($table, 'first_name') && Schema::hasColumn($table, 'last_name')) {
                return "TRIM(CONCAT_WS(' ', `$alias`.`first_name`, `$alias`.`last_name`))";
            }

            return null;
        }

        return Schema::hasColumn($table, $column) ? "`$alias`.`$column`" : null;
    }

    /**
     * Resolve the SQL expression a computed sort key is ordered by, from the
     * model's `sortableExpressions()` map. Lets a listing sort by a value the
     * resource derives rather than reads (`remaining_days`), which no column
     * holds and which would otherwise silently fall back to `id`.
     */
    protected function resolveSortExpression(Model $model, ?string $requested): ?string
    {
        if (! $requested || ! method_exists($model, 'sortableExpressions')) {
            return null;
        }

        return $model->sortableExpressions()[$requested] ?? null;
    }

    /**
     * Resolve the lookup enum the sort column should be ordered by its
     * translated label, from two sources:
     *
     *   1. An explicit `sortableEnums()` map on the model, for enum columns
     *      that are not cast to the enum (e.g. `causes.status`, kept as a raw
     *      string because it is compared/stored as one across the codebase).
     *   2. Automatic detection from the model's casts, limited to
     *      string-backed enums — int-backed enums (steps, statuses, ...) carry
     *      a deliberate numeric order and keep sorting by their stored value.
     *
     * @return class-string|null
     */
    protected function resolveSortEnum(Model $model, string $sortColumn): ?string
    {
        // 1. Explicit opt-in wins and is trusted regardless of backing type.
        if (method_exists($model, 'sortableEnums')) {
            $enumClass = $model->sortableEnums()[$sortColumn] ?? null;

            if ($enumClass && method_exists($enumClass, 'getList')) {
                return $enumClass;
            }
        }

        // 2. Automatic detection from a string-backed enum cast.
        $enumClass = $model->getCasts()[$sortColumn] ?? null;

        if (! is_string($enumClass) || ! enum_exists($enumClass)) {
            return null;
        }

        if ((new \ReflectionEnum($enumClass))->getBackingType()?->getName() !== 'string') {
            return null;
        }

        return method_exists($enumClass, 'getList') ? $enumClass : null;
    }

    /**
     * Order the query by an enum column's translated label rather than its
     * stored value. A CASE expression maps each stored value to its label so
     * the database sorts using its own (locale-aware) collation.
     *
     * @param  class-string  $enumClass
     */
    protected function applyEnumOrder(Builder $query, string $enumClass, string $sortColumn, string $sortDirection): Builder
    {
        $list = $enumClass::getList();

        if (empty($list)) {
            return $query->orderBy($sortColumn, $sortDirection);
        }

        $column = $query->qualifyColumn($sortColumn);
        $sql = 'CASE '.$column;
        $bindings = [];

        foreach ($list as $item) {
            $sql .= ' WHEN ? THEN ?';
            $bindings[] = $item['value'];
            $bindings[] = $item['label'];
        }

        // Unknown values fall back to the raw column so they still sort deterministically.
        $sql .= ' ELSE '.$column.' END '.$sortDirection;

        return $query->orderByRaw($sql, $bindings);
    }

    /**
     * Order the query by a resolved sort column.
     *
     * A JSON path (`name->en`) is extracted by MySQL as `utf8mb4_bin`, which
     * compares byte by byte: every lowercase name then sorts after every
     * uppercase one (`test` before `Legal` descending). Lower-casing both sides
     * of the comparison restores the expected alphabetical order, and leaves
     * non-latin scripts untouched.
     */
    protected function applyOrder(Builder $query, string $sortColumn, string $direction): Builder
    {
        if (! str_contains($sortColumn, '->')) {
            return $query->orderBy($sortColumn, $direction);
        }

        $wrapped = $query->getQuery()->getGrammar()->wrap($sortColumn);

        return $query->orderByRaw("LOWER($wrapped) $direction");
    }

    /**
     * Strip the prefix a listing column carries so it resolves to the column it
     * is built from: `translation_name` to `name`, `display_type` to `type`.
     * A prefixed name that matches no column is left as it is.
     */
    protected function resolveDisplayedColumn(string $table, string $requested): string
    {
        foreach (['translation_', 'display_'] as $prefix) {
            if (str_starts_with($requested, $prefix)) {
                $column = substr($requested, strlen($prefix));

                return Schema::hasColumn($table, $column) ? $column : $requested;
            }
        }

        return $requested;
    }

    /**
     * The attributes a model stores as translated JSON, e.g. `name`.
     *
     * @return array<int, string>
     */
    protected function translatableAttributes(Model $model): array
    {
        return method_exists($model, 'getTranslatableAttributes') ? $model->getTranslatableAttributes() : [];
    }

    protected function resolveSortColumn(Model $model, ?string $requested): string
    {
        $table = $model->getTable();

        try {
            if (! $requested) {
                return 'id';
            }

            // Handle JSON dot notation like name.en => name->en
            if (str_starts_with($requested, 'name') && Schema::hasColumn($table, 'name')) {
                $jsonKey = explode('.', $requested)[1] ?? null;
                if ($jsonKey && preg_match('/^[A-Za-z_-]+$/', $jsonKey)) {
                    return "name->$jsonKey";
                }
            }

            // Listings name their columns after what they display rather than
            // after the column behind them: a translated value as
            // `translation_name`, an enum label as `display_type`. Both sort by
            // the column they are built from.
            $requested = $this->resolveDisplayedColumn($table, $requested);

            // A translatable column holds every locale in one JSON document
            // (`{"ar": "...", "en": "..."}`). Ordering by the raw column compares
            // the documents key by key, so the `ar` value always decides the order
            // and other locales come out unsorted. Sort by the active locale.
            if (in_array($requested, $this->translatableAttributes($model), true) && Schema::hasColumn($table, $requested)) {
                return $requested.'->'.app()->getLocale();
            }

            if (Schema::hasColumn($table, $requested)) {
                return $requested;
            }

            return 'id';
        } catch (\Exception $e) {
            Log::warning('Failed to resolve sort column: '.$e->getMessage());

            return 'id';
        }
    }

    protected function resolveSortDirection(?string $direction = null): string
    {
        return $direction && in_array(strtolower($direction), ['asc', 'desc'])
            ? strtolower($direction)
            : 'desc';
    }
}
