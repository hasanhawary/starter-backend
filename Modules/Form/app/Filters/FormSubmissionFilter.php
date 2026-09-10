<?php

namespace Modules\Form\app\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FormSubmissionFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        $query = $next($query);

        $query->when(request()->has('source_id') && request()->has('source_type'), function ($q) {
            $sourceModel = resolveModel(request('source_type'), request('source_module'));

            $q->where('source_type', $sourceModel?->getMorphClass())->where('source_id', request('source_id'));
        });

        $query->when(
            request()->has('form_id'),
            fn ($q) => $q->where('form_id', request('form_id'))
        );

        return $query;
    }
}
