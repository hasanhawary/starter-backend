<?php

namespace Modules\Notification\app\Listeners;

use Modules\Notification\app\Tools\Facades\Notification;

class SendScheduleNotificationListener
{
    public function handle($data): void
    {
        Notification::scheduled($data->events);
    }
}
