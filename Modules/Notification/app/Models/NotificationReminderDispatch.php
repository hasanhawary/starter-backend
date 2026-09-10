<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Dedupe ledger for scheduled reminders: one row per claimed
 * (reminders_setting, model, target_date) dispatch. Deleting a row allows the
 * same reminder to be sent again (manual retry escape hatch).
 */
class NotificationReminderDispatch extends Model
{
    protected $fillable = [
        'reminders_setting_id',
        'notification_event_id',
        'model_type',
        'model_id',
        'target_date',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function remindersSetting(): BelongsTo
    {
        return $this->belongsTo(RemindersSetting::class, 'reminders_setting_id');
    }

    public function notificationEvent(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
