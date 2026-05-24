<?php

namespace App\Services\Global;

use Illuminate\Database\Eloquent\Builder;

class QueryHelper
{
    /**
     * Add a search condition for JSON fields in multiple languages.
     *
     * @param  bool  $isExact  To determine if the search should be exact or a partial match
     */
    public static function applyJsonSearch(Builder $query, string $field, string|array $search, bool $isExact = false): Builder
    {
        return $query->where(function ($q) use ($field, $search, $isExact) {
            foreach (config('app.supported_languages', ['ar', 'en']) as $language) {
                $searchQuery = is_array($search) ? ($search[$language] ?? '') : $search;
                $searchQuery = $isExact ? $searchQuery : "%$searchQuery%";
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT($field, '$.$language')) LIKE ?", [$searchQuery]);
            }
        });
    }
}
