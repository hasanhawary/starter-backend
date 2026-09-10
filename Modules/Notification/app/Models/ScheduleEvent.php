<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Notification\app\Enum\ScheduleEventSourceEnum;
use Modules\Notification\app\Enum\ScheduleEventTypeEnum;
use Spatie\Translatable\HasTranslations;

class ScheduleEvent extends Model
{
    use HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'body'];

    public bool $inPermission = true;

    public array $specialOperations = [];

    protected $fillable = [
        'date_time',
        'title',
        'body',
        'notification_event_id',
        'receiver_id',
        'receiver_type',
        'source_id',
        'source_type',
        'type',
        'status',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'type' => ScheduleEventTypeEnum::class,
    ];

    /**
     * Columns to sort by their translated label rather than their stored value.
     *
     * A calendar row displays the source it was raised for as its type, and
     * `source_type` stores that source's class name, so ordering by the raw
     * column sorts by the class name instead of by the displayed label.
     *
     * @return array<string, class-string>
     */
    public function sortableEnums(): array
    {
        return [
            'source_type' => ScheduleEventSourceEnum::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes Methods
    |--------------------------------------------------------------------------
    */
    public function scopeCalendar($query)
    {
        return $query->where('type', ScheduleEventTypeEnum::Calendar);
    }

    public function scopeReminder($query)
    {
        return $query->where('type', ScheduleEventTypeEnum::Reminder);
    }

    public function scopeByCurrentUser($query)
    {
        return $query->where('receiver_id', auth()->id())
            ->where('receiver_type', auth()->user() ? get_class(auth()->user()) : null);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function notificationEvent(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class);
    }

    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scheduleEventReceivers(): HasMany
    {
        return $this->hasMany(ScheduleEventReceiver::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }
}
