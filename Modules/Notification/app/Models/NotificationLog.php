<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notification_event_id',
        'notification_template_id',
        'notifiable_id',
        'notifiable_type',
        'payload',
        'status',
        'retry_count',
        'error_message',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'payload' => 'array',

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

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
