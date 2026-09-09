<?php

namespace Modules\Notification\app\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduleNotificationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Collection $events) {}
}
