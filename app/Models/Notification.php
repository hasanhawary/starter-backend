<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

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
            'notifiable_type' => Admin::class,
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
