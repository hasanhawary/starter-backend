<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Notification extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'id' => 'string',
        'data' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        return $query->where([
            'notifiable_type' => User::class,
            'notifiable_id' => auth()->id(),
        ]);
    }

    public function markAsOpen(): void
    {
        $this->whereNull('open_at')->update(['open_at' => now()]);
    }

    public function markAsRead(?array $ids = []): void
    {
        $this->whereIn('id', $ids)->update(['read_at' => now()]);
    }
}
