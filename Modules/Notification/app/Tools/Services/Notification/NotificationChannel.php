<?php

namespace Modules\Notification\Tools\Services\Notification;

class NotificationChannel
{
    public function via($channel): array
    {
        return [$channel];
    }

    public function send($notifiable) {}
}
