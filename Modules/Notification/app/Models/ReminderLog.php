<?php

namespace Modules\Notification\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\app\Enum\NotificationChannelEnum;

class ReminderLog extends Model
{
    protected $fillable = [
        'schedule_event_id',
        'notification_event_id',
        'user_id',
        'channel',
        'payload',
        'status',
        'error_message',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'channel' => NotificationChannelEnum::class,
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function scheduleEvent(): BelongsTo
    {
        return $this->belongsTo(ScheduleEvent::class);
    }

    public function notificationEvent(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

