<?php

namespace Modules\Showcase\app\Trait;

use App\Models\User;
use App\Trait\Global\HasPinMethods;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Per-user pinning. `pinUsers()` is the relation
 * {@see HasPinMethods} toggles, and `$pinPivotData` is the
 * payload it writes onto the pivot row.
 */
trait HasShowcasePins
{
    /**
     * Extra pivot columns written when a record is pinned. Empty because the
     * pin table only records who pinned what and when.
     *
     * @var array<string, mixed>
     */
    public array $pinPivotData = [];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function pinUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'showcase_user_pins', 'showcase_id', 'user_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    /**
     * Records the given user (default: the authenticated one) has pinned.
     */
    public function scopePinnedBy(Builder $query, ?int $userId = null): Builder
    {
        return $query->whereHas('pinUsers', fn (Builder $q) => $q->whereKey($userId ?? auth()->id()));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function isPinnedBy(?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return false;
        }

        return $this->relationLoaded('pinUsers')
            ? $this->pinUsers->contains('id', $userId)
            : $this->pinUsers()->whereKey($userId)->exists();
    }
}
