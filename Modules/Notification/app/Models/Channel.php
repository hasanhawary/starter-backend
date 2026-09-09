<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Channel extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];
    public bool $inPermission = true;
    public array $specialOperations = [];

    protected $fillable = [
        'name',
        'driver',
        'slug',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function notificationEvents(): BelongsToMany
    {
        return $this->belongsToMany(NotificationEvent::class, 'event_channel', 'channel_id', 'event_id');
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}

