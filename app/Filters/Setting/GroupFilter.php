<?php

namespace App\Filters\Setting;

use Closure;

class GroupFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        when(request('group'), static fn () => $query->where('group', 'like', '%'.request('group').'%'));

        return $query;
    }
}
