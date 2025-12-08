<?php

namespace App\Filters\Global;

use App\Services\Global\QueryHelper;
use Closure;

class JsonNameFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);
        $search = request('search');

        when($search, static fn() => QueryHelper::applyJsonSearch($query, 'name', $search));

        return $query;
    }
}
