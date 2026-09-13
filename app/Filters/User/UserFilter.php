<?php

namespace App\Filters\User;

use App\Filters\BaseFilter;
use App\Trait\Global\AdvancedFilter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends BaseFilter
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
            // A grouped multi-select submits value as an array of arrays; the
            // base normalizer flattens it into the union AdvancedFilter expects.
            $this->filter['advanced'] = $this->normalizeAdvancedFilters(request('advanced'));
        }
    }

    public function handle($request, Closure $next)
    {
        $query = $next($request);

        $this->applySearchFilter($query)
            ->applyAdvancedFilter($query);

        $this->applyDateRangeFilter($query, 'created_at');
        $this->applyDateRangeFilter($query, 'last_login');

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
