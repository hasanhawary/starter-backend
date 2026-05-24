<?php

namespace Modules\Export\app\Filters;

use Closure;

class ExportFilter
{
    public function handle($query, Closure $next)
    {
        $type = request('exportable_type');

        if (! empty($type)) {
            $query->where('exportable_type', $type);
        }

        return $next($query);
    }
}
