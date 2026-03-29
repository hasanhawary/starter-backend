<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends BaseModel
{
    public bool $inPermission = false;

    protected $fillable = [
        'user_id',
        'setting',
    ];

    protected $casts = [
        'setting' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
