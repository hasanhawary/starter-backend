<?php

namespace App\Filters\Central\Setting;

use Closure;

class KeyFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        when(request('key'), static fn() => $query->where('key', 'like', '%' . request('key') . '%'));

        return $query;
    }
}
