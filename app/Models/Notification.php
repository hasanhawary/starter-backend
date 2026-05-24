<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Notification extends BaseModel
{
    protected $fillable = ['type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'open_at'];

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
}
