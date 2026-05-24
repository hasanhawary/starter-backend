<?php

namespace App\Filters\Notification;

use Closure;
use Illuminate\Support\Arr;

class NotificationFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(
            request()->has('group'),
            fn ($query) => $query->whereIn('data->group', Arr::wrap(request('group'))),
        );

        return $query;
    }
}
