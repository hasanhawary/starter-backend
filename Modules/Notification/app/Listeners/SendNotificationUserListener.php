<?php

namespace Modules\Notification\app\Listeners;

use Modules\Notification\app\Events\NotificationUserEvent;
use Modules\Notification\app\Tools\Facades\Notification;

class SendNotificationUserListener
{
    public function handle(NotificationUserEvent $data): void
    {
        Notification::send($data->eventName, $data->data['model'] ?? null);
    }
}
