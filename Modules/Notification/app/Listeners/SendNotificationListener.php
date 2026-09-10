<?php

namespace Modules\Notification\app\Listeners;


use Modules\Notification\app\Tools\Facades\Notification;

class SendNotificationListener
{
    public function handle($data): void
    {
        Notification::send($data->eventName, $data->model);
    }
}
