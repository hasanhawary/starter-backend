<?php

namespace App\Filters\Global;

use Carbon\Carbon;
use Closure;

class DateFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        if (!empty(request('start'))) {
            $query->whereDate('created_at', '>=', Carbon::parse(request('start'))->format('Y-m-d'));
        }

        if (!empty(request('end'))) {
            $query->whereDate('created_at', '<=', Carbon::parse(request('end'))->format('Y-m-d'));
        }

        return $query;
    }
}
