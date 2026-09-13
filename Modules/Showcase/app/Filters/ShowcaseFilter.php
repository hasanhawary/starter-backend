<?php

namespace Modules\Showcase\app\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pipeline filter for the showcase listing. Every parameter is optional and
 * each one narrows the query independently, so the frontend can send any
 * combination.
 *
 * Each parameter maps to one named thing: a column, a relation, or one of the
 * model's own scopes. There is no generic "run the scope I name" parameter —
 * a request can only reach the narrowing this class spells out.
 */
class ShowcaseFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        $query = $next($query);

        // Free text over the translated name, the description and the reference.
        $query->when(request('search'), function (Builder $q, string $search) {
            $q->where(function (Builder $inner) use ($search) {
                $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere('name->'.app()->getLocale(), 'like', "%{$search}%")
                    ->orWhere('description->'.app()->getLocale(), 'like', "%{$search}%");
            });
        });

        $query->when(request()->filled('status'), fn (Builder $q) => $q->status(request('status')));
        $query->when(request()->filled('priority'), fn (Builder $q) => $q->whereIn('priority', resolveArray(request('priority'))));
        $query->when(request()->filled('visibility'), fn (Builder $q) => $q->whereIn('visibility', resolveArray(request('visibility'))));
        $query->when(request()->filled('showcase_category_id'), fn (Builder $q) => $q->whereIn('showcase_category_id', resolveArray(request('showcase_category_id'))));
        $query->when(request()->filled('owner_id'), fn (Builder $q) => $q->whereIn('owner_id', resolveArray(request('owner_id'))));
        $query->when(request()->filled('created_by'), fn (Builder $q) => $q->whereIn('created_by', resolveArray(request('created_by'))));

        $query->when(request()->filled('tag_id'), fn (Builder $q) => $q->whereHas(
            'tags',
            fn (Builder $tags) => $tags->whereIn('showcase_tags.id', resolveArray(request('tag_id')))
        ));

        // Flags that read as a question about the record, each backed by a scope.
        $query->when(request()->boolean('pinned'), fn (Builder $q) => $q->pinnedBy());
        $query->when(request()->boolean('mine'), fn (Builder $q) => $q->ownedBy());
        $query->when(request()->boolean('published'), fn (Builder $q) => $q->published());
        $query->when(request()->filled('expiring_within'), fn (Builder $q) => $q->expiringWithin((int) request('expiring_within')));

        $query->when(request('expires_at_from'), fn (Builder $q, $from) => $q->whereDate('expires_at', '>=', $from));
        $query->when(request('expires_at_to'), fn (Builder $q, $to) => $q->whereDate('expires_at', '<=', $to));

        return $query;
    }
}
