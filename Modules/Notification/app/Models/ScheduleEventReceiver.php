<?php

namespace Modules\Notification\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEventReceiver extends Model
{
    protected $fillable = [
        'schedule_event_id',
        'user_id',
        'read_at',
        'verified_at',
        'status',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function scheduleEvent(): BelongsTo
    {
        return $this->belongsTo(ScheduleEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
