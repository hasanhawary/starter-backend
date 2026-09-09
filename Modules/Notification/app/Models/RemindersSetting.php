<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\ReminderSettingTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingUnitsEnum;

class RemindersSetting extends Model
{
    public bool $inPermission = true;

    public array $specialOperations = [];

    protected $table = 'reminders_setting';

    protected $fillable = [
        'notification_event_id',
        'channel',
        'offset_unit',
        'offset_type',
        'offset_value',
        'reminder_based_on_column',
    ];

    protected $casts = [
        'channel' => NotificationChannelEnum::class,
        'offset_unit' => ReminderSettingUnitsEnum::class,
        'offset_type' => ReminderSettingTypesEnum::class,
        'offset_value' => 'integer',
        'reminder_based_on_column' => 'integer',
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

    public function verifiableDate(): BelongsTo
    {
        return $this->belongsTo(NotificationVerifiableDate::class, 'reminder_based_on_column');
    }
}
