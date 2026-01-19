<?php

namespace App\Filters\Global;

use Closure;

class NameFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        when(request('search'), static fn() => $query->where('name', 'like', '%' . request('search') . '%'));

        return $query;
    }
}
