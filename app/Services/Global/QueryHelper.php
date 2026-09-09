<?php

namespace App\Services\Global;

use Illuminate\Database\Eloquent\Builder;

class QueryHelper
{
    /**
     * Add a search condition for JSON fields in multiple languages.
     *
     * Both sides are lower-cased so a search — and an exact uniqueness check —
     * ignores case the way the plain text columns of a search group do.
     *
     * @param  bool  $isExact  To determine if the search should be exact or a partial match
     */
    public static function applyJsonSearch(Builder $query, string $field, string|array $search, bool $isExact = false): Builder
    {
        return $query->where(function ($q) use ($field, $search, $isExact) {
            foreach (config('app.supported_languages', ['ar', 'en']) as $language) {
                $searchQuery = is_array($search) ? ($search[$language] ?? '') : $search;
                $searchQuery = $isExact ? $searchQuery : "%$searchQuery%";
                $q->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT($field, '$.$language'))) LIKE LOWER(?)", [$searchQuery]);
            }
        });
    }

    /**
     * Add an `id` match to an existing search group.
     *
     * The match is a prefix one, so `1` returns 1, 10, 11, 12… the same way the
     * text columns of a search group match partially. The condition is only
     * applied for numeric search terms, otherwise MySQL silently casts the term
     * to `0` and matches unrelated rows.
     *
     * @param  string|null  $column  Defaults to the query's own (possibly aliased) `id` column.
     */
    public static function applyIdSearch(Builder $query, string|int|null $search, ?string $column = null): Builder
    {
        if (! is_numeric($search)) {
            return $query;
        }

        $column ??= self::qualifiedIdColumn($query);
        $search = (string) $search;

        /** Signed/decimal/exponent terms can never prefix an id, so they stay exact matches. */
        if (! ctype_digit($search)) {
            return $query->orWhere($column, $search);
        }

        return $query->orWhere($column, 'like', $search.'%');
    }

    /**
     * Resolve the `id` column for the table the query currently reads from.
     */
    private static function qualifiedIdColumn(Builder $query): string
    {
        $from = $query->getQuery()->from;

        if (! is_string($from)) {
            return 'id';
        }

        // Self-referencing `whereHas` constraints run against an aliased table
        // (e.g. `users as laravel_reserved_0`), so qualify with the alias.
        if (preg_match('/\s+as\s+(\S+)$/i', $from, $matches)) {
            return $matches[1].'.id';
        }

        return $from.'.id';
    }
}
