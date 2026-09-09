<?php

namespace Modules\Notification\app\Tools\Factory;

use Modules\Notification\app\Tools\Contracts\SmsServiceInterface;
use Modules\Notification\Tools\Services\Sms\TwilioService;

class SmsFactory
{
    public static function make(string $gateway): SmsServiceInterface
    {

        return match ($gateway) {
            'twilio' => new TwilioService,
            // 'vonage' => new VonageService,
            default => throw new \InvalidArgumentException("Invalid SMS Gateway: {$gateway}"),
        };
    }

    public static function default(): SmsServiceInterface
    {
        $gateway = config('notification.default_sms_driver', 'twilio');

        return self::make($gateway);
    }
}
