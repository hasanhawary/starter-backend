<?php

namespace App\Filters\User;

use App\Trait\Global\AdvancedFilter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class UserFilter
{
    use AdvancedFilter;

    protected array $filter = [];

    protected array $relations = [];

    public function __construct()
    {
        $this->relations = [
            'many' => [
                'created_by' => ['relation' => 'creator'],
            ],
        ];

        if (request()->input('advanced') && is_array(request('advanced'))) {
            $this->filter['advanced'] = request('advanced');
        }
    }

    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $this->applySearchFilter($query)
            ->applyAdvancedFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): static
    {
        $query->when(request()->has('search') && ! empty(request('search')), function ($query) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%'.request('search').'%')
                    ->orWhere('email', 'like', '%'.request('search').'%')
                    ->orWhere('phone', 'like', '%'.request('search').'%');
            });
        });

        return $this;
    }
}
