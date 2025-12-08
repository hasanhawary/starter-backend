<?php

namespace App\Filters\Global;

use Closure;

class TrashedFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request('trashed', false), fn($q) => $q->onlyTrashed());

        return $query;
    }
}
