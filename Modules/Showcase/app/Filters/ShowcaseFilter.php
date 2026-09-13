<?php

namespace Modules\Showcase\app\Filters;

use App\Filters\BaseFilter;
use App\Trait\Global\AdvancedFilter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pipeline filter for the showcase listing.
 *
 * The select options `config/discovery.php` declares for this module are
 * submitted through the shared `advanced[]` contract and resolved by
 * {@see AdvancedFilter}; the date ranges and the boolean flags are top-level
 * params, matching what discovery declares for them.
 */
class ShowcaseFilter extends BaseFilter
{
    use AdvancedFilter;

    /** @var array<string, mixed> */
    protected array $filter = [];

    /** @var array<string, array<string, mixed>> */
    protected array $relations = [];

    public function __construct()
    {
        $this->relations = [
            'many' => [
                // Every other advanced key is a column on `showcases`, which
                // AdvancedFilter resolves on its own.
                'tag_id' => ['relation' => 'tags', 'column' => 'showcase_tags.id'],
            ],
        ];

        if (request()->input('advanced') && is_array(request('advanced'))) {
            $this->filter['advanced'] = $this->normalizeAdvancedFilters(request('advanced'));
        }
    }

    public function handle($request, Closure $next): Builder
    {
        $query = $next($request);

        $this->applySearchFilter($query)
            ->applyAdvancedFilter($query);

        $this->applyDateRangeFilter($query, 'created_at');
        $this->applyDateRangeFilter($query, 'expires_at');
        $this->applyFlags($query);

        return $query;
    }

    /**
     * Free text over the translated name, the description and the reference.
     */
    private function applySearchFilter(Builder $query): static
    {
        $query->when(request('search'), function (Builder $q, string $search) {
            $locale = app()->getLocale();

            $q->where(function (Builder $inner) use ($search, $locale) {
                $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere("name->{$locale}", 'like', "%{$search}%")
                    ->orWhere("description->{$locale}", 'like', "%{$search}%");
            });
        });

        return $this;
    }

    /**
     * Flags that read as a question about the record, each backed by a scope.
     */
    private function applyFlags(Builder $query): void
    {
        $query->when(request()->boolean('pinned'), fn (Builder $q) => $q->pinnedBy());
        $query->when(request()->boolean('mine'), fn (Builder $q) => $q->ownedBy());
        $query->when(request()->boolean('published'), fn (Builder $q) => $q->published());
        $query->when(
            request()->filled('expiring_within'),
            fn (Builder $q) => $q->expiringWithin((int) request('expiring_within'))
        );
    }
}
