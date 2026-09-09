<?php

namespace App\Filters\Global;

use Closure;
use Illuminate\Support\Facades\Schema;

class OrderColumnFilter
{
    /**
     * Sort the query by the `order` column ascending when the table has one.
     *
     * Place this filter last in the pipeline so its ordering takes precedence
     * over any other sort (e.g. OrderByFilter), making `order` the primary sort.
     */
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        if (Schema::hasColumn($query->getModel()->getTable(), 'order')) {
            $query->orderBy('order');
        }

        return $query;
    }
}
