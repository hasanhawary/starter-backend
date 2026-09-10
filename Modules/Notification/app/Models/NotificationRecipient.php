<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationRecipient extends Model
{
    public bool $inPermission = true;
    public array $specialOperations = [];

    protected $fillable = [
        'recipientable_id',
        'recipientable_type',
        'type',
        'notification_event_id',
    ];

    protected $casts = [
        'type' => 'string',
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

    public function recipientable(): MorphTo
    {
        return $this->morphTo();
    }
}

