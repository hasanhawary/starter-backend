<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Spatie\Translatable\HasTranslations;

class NotificationTemplate extends Model
{
    use HasTranslations;

    public bool $inPermission = true;

    public array $specialOperations = [];

    protected array $translatable = [
        'title',
        'body',
    ];

    protected $fillable = [
        'title',
        'body',
        'channel',
        'date_column',
        'notification_event_id',
    ];

    protected $casts = [
        'template' => 'array',
        'channel' => NotificationChannelEnum::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function notificationEvent(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class);
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
