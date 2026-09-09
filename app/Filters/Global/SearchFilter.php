<?php

namespace App\Filters\Global;

use App\Services\Global\QueryHelper;
use Closure;

class SearchFilter
{
    public function handle($request, Closure $next): mixed
    {
        $query = $next($request);

        if (! empty(request('search'))) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%']);

                QueryHelper::applyIdSearch($q, $search);
            });
        }

        return $query;
    }
}
