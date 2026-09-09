<?php

namespace Modules\Notification\app\Tools\Factory;


use Modules\Notification\app\Tools\Channels\SmsChannel;
use Modules\Notification\app\Tools\Channels\CalendarChannel;
use Modules\Notification\app\Tools\Channels\EmailChannel;
use Modules\Notification\app\Tools\Channels\NotificationChannel;
use Modules\Notification\app\Tools\Channels\PushChannel;
use Modules\Notification\app\Tools\Channels\ReminderChannel;

class NotificationChannelFactory
{
    public static function make(string $channel): mixed
    {
        return match ($channel) {
            'sms' => new SmsChannel,
            'push' => new PushChannel,
            'email' => new EmailChannel,
            'notification' => new NotificationChannel,
            'reminder' => new ReminderChannel,
            'calendar' => new CalendarChannel,
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }
}
