<?php

namespace App\Filters\Global;

use Closure;

class PhoneFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        when(request('search'), static fn() => $query->where('phone', 'like', '%' . request('search') . '%'));

        return $query;
    }
}
