<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class SystemNotification extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'body'];

    public bool $inPermission = true;

    public array $specialOperations = [];

    protected $fillable = [
        'notification_event_id',
        'title',
        'body',
        'meta',
        'channel_id',
        'notifiable_id',
        'notifiable_type',
        'read_at',
        'notification_template_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
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

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
