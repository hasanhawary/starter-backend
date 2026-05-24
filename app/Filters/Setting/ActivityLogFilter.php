<?php

namespace App\Filters\Setting;

use Carbon\Carbon;
use Closure;

class ActivityLogFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request('search'), function ($query) use ($request) {
            $query->where('description', 'like', '%'.$request->search.'%');
        })
            ->when(request('model'), fn ($query) => $query->where('subject_type', detectModelPath(request('model'))))
            ->when(request('user_id'), fn ($query) => $query->where('causer_id', request('user_id')))
            ->when(request('operation'), fn ($query) => $query->where('description', request('operation')))
            ->when(request('date_from'), fn ($query) => $query->whereDate('created_at', '>=', Carbon::parse(request('date_from'))))
            ->when(request('date_to'), fn ($query) => $query->whereDate('created_at', '<=', Carbon::parse(request('date_to'))));

        return $query;
    }
}
