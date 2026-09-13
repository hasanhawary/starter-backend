<?php

namespace Modules\Showcase\app\Scopes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Policies\ShowcasePolicy;

/**
 * The reusable query shapes of {@see Showcase}, in one place so a controller
 * never has to build one inline:
 *
 *  - which records a caller may see (`visibleTo`, `active`, `published`, ...);
 *  - which related data a representation needs, as a scope for a listing query
 *    (`withListingData`) and as the matching loader for one already-resolved
 *    record (`loadDetailData`).
 *
 * Every scope here has a named consumer — the controller, the Policy, the
 * Pipeline filter, or the `help-models` lookup — and none is a generic
 * escape hatch driven by raw request input.
 */
trait ShowcaseScopes
{
    /*
    |--------------------------------------------------------------------------
    | Selection scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Records that really reached the published state, date included — stricter
     * than filtering `status` alone.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ShowcaseStatusEnum::Published->value)
            ->whereNotNull('published_at');
    }

    public function scopeOwnedBy(Builder $query, ?int $userId = null): Builder
    {
        return $query->where('owner_id', $userId ?? auth()->id());
    }

    public function scopeStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', resolveArray($status));
    }

    /**
     * The records a user is allowed to read: public ones, internal ones for any
     * authenticated user, and private ones only for their owner or creator.
     *
     * Mirrors {@see ShowcasePolicy::view()} so a
     * listing can never show a record `show` would refuse.
     */
    public function scopeVisibleTo(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= auth()->id();

        return $query->where(function (Builder $q) use ($userId) {
            $q->where('visibility', ShowcaseVisibilityEnum::Public->value);

            if (! $userId) {
                return;
            }

            $q->orWhere('visibility', ShowcaseVisibilityEnum::Internal->value)
                ->orWhere(function (Builder $own) use ($userId) {
                    $own->where('visibility', ShowcaseVisibilityEnum::Private->value)
                        ->where(fn (Builder $mine) => $mine->where('owner_id', $userId)->orWhere('created_by', $userId));
                });
        });
    }

    /**
     * Records whose `expires_at` falls inside the next N days — the query the
     * reminder notification channel is built around.
     */
    public function scopeExpiringWithin(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Representation scopes
    |--------------------------------------------------------------------------
    */
    /**
     * Everything a listing row shows: the relations the Resource reads, the pin
     * count, and whether the viewer pinned the record — all resolved in SQL, so
     * a page of rows costs a fixed number of queries.
     */
    public function scopeWithListingData(Builder $query, ?int $viewerId = null): Builder
    {
        return $query->with($this->resourceRelations())
            ->withCount('pinUsers')
            ->withExists($this->pinnedByViewer($viewerId));
    }

    /**
     * The same data on one already-resolved record, plus the note thread a
     * single-record response carries. The `load*` counterpart of
     * {@see scopeWithListingData()}, for a route-bound model.
     */
    public function loadDetailData(?int $viewerId = null): static
    {
        return $this->load([...$this->resourceRelations(), 'notes.author'])
            ->loadCount('pinUsers')
            ->loadExists($this->pinnedByViewer($viewerId));
    }

    /**
     * Re-read this record inside the caller's open transaction, holding a row
     * lock until it commits.
     *
     * Route-model binding resolves the record *before* the transaction and
     * without a lock, so two reviewers acting at the same moment would both see
     * the old status, both pass the transition guard, and both write a log entry
     * and a notification. Taking the lock is the only reason the record is read
     * a second time.
     */
    public function lockFresh(): static
    {
        return $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * Relations every showcase representation reads.
     *
     * @return array<int, string>
     */
    protected function resourceRelations(): array
    {
        return ['category', 'owner', 'creator', 'tags'];
    }

    /**
     * The `is_pinned` existence constraint, shared by the query and the loader
     * so both spell the flag the Resource reads the same way.
     *
     * @return array<string, Closure>
     */
    protected function pinnedByViewer(?int $viewerId = null): array
    {
        $viewerId ??= auth()->id();

        return ['pinUsers as is_pinned' => fn (Builder $pins) => $pins->whereKey($viewerId)];
    }
}
