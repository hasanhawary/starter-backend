<?php

namespace App\Filters\Central\Subscription;

use App\Services\Global\QueryHelper;
use Closure;

class PlanFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);
        $search = request('search');

        $query->when(!empty(request($search)), function ($query) use ($search) {
            when($search, static fn() => QueryHelper::applyJsonSearch($query, 'name', $search));

            $query->orWhere('code', 'like', "%$search%");
        });

        return $query;
    }
}
