<?php

namespace App\Filters\Central\Admin;

use Closure;

class AdminFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $query->when(request()->has('search') && !empty(request('search')), function ($query) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('email', 'like', '%' . request('search') . '%')
                    ->orWhere('phone', 'like', '%' . request('search') . '%');
            });
        });

        return $query;
    }
}
