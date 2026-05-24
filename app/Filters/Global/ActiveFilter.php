<?php

namespace App\Filters\Global;

use Closure;

class ActiveFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(
            request()->has('is_active'),
            fn ($query) => $query->where('is_active', (bool) request('is_active')),
        );

        return $query;
    }
}
