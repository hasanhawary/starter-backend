<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

abstract class BaseFilter
{
    /**
     * Flatten every advanced item's value one level deep. Grouped options
     * (e.g. the causes `status` filter) carry an array as their value, so a
     * multi-select submits value as an array of arrays — flattening turns a
     * selection of groups into the plain union of their values, which is
     * what the AdvancedFilter trait's whereIn expects. Original array keys
     * are preserved for filters that still accept the legacy associative
     * advanced shape.
     */
    protected function normalizeAdvancedFilters(?array $advanced): array
    {
        return collect($advanced ?? [])
            ->map(function ($item) {
                $value = data_get($item, 'value');

                if (is_array($value)) {
                    data_set($item, 'value', array_values(Arr::flatten($value)));
                }

                return $item;
            })
            ->all();
    }

    /**
     * Apply a discovery `date_range` filter to the query.
     *
     * Every range declared in config/discovery.php is submitted as
     * `{key}_from` / `{key}_to`, and either bound may be sent alone, which
     * leaves that side of the range open — so the bounds are applied as two
     * independent comparisons rather than a whereBetween with defaults.
     *
     * @param  string  $key  request key the range is submitted under
     * @param  string|null  $column  target column, defaults to the request key
     *                               qualified with the model's table
     */
    protected function applyDateRangeFilter(Builder $query, string $key, ?string $column = null): Builder
    {
        // Qualify the default column so a range never becomes ambiguous once
        // the listing joins another table carrying the same column name.
        $column ??= $query->qualifyColumn($key);

        if (! empty(request("{$key}_from"))) {
            $query->whereDate($column, '>=', Carbon::parse(request("{$key}_from"))->format('Y-m-d'));
        }

        if (! empty(request("{$key}_to"))) {
            $query->whereDate($column, '<=', Carbon::parse(request("{$key}_to"))->format('Y-m-d'));
        }

        return $query;
    }

    /**
     * Apply a generic name filter to the query builder.
     */
    protected function applyNameFilter(Builder $query, string $searchTerm): Builder
    {
        return $query->whereRaw('LOWER(name) LIKE LOWER(?)', [$searchTerm]);
    }

    protected function applySmartNameFilter(Builder $query, string $searchTerm): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->where(function ($q) use ($searchTerm, $table) {
            if (Schema::hasColumn($table, 'name')) {
                $q->orWhereRaw('LOWER(name) LIKE LOWER(?)', [$searchTerm]);
            }

            if (Schema::hasColumn($table, 'first_name') && Schema::hasColumn($table, 'last_name')) {
                $q->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?)", [$searchTerm]);
            }
        });
    }
}
