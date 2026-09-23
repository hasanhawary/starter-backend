<?php

namespace App\Filters\Setting;

use App\Filters\BaseFilter;
use Closure;

class ActivityLogFilter extends BaseFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request('search'), function ($query) use ($request) {
            $query->where('description', 'like', '%'.$request->search.'%');
        })
            ->when(request('model'), fn ($query) => $query->where('subject_type', detectModelPath(request('model'))))
            ->when(request('user_id'), fn ($query) => $query->where('causer_id', request('user_id')))
            ->when(request('operation'), fn ($query) => $query->where('description', request('operation')));

        // Same `date_from` / `date_to` request contract, applied through the
        // shared range helper against the qualified column.
        $this->applyDateRangeFilter($query, 'date', $query->qualifyColumn('created_at'));

        return $query;
    }
}
