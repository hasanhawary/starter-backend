<?php

namespace App\Services\Global;

use App\Events\NotificationEvent;
use App\Jobs\SendSmsJob;
use App\Mail\BasicMail;
use App\Notifications\UserNotify;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function resolve(Authenticatable $user, array $data, ?array $types = ['notify', 'realtime']): void
    {
        foreach ($types as $type) {
            try {
                match ($type) {
                    'realtime' => self::sendRealtimeNotification($user, $data),
                    'notify' => self::sendNotify($user, $data),
                    'email' => self::sendEmail($user, $data),
                    'sms' => self::sendSMS($user, $data),
                    default => null,
                };
            } catch (\Exception|\Error $exception) {
                logError($exception);
            }
        }
    }

    private static function sendNotify(Authenticatable $user, array $data): void
    {
        $user->notify(new UserNotify($data));
    }

    private static function sendSMS(Authenticatable $user, array $data): void
    {
        $message = self::resolveMessageContent($data);

        if ($user->phone) {
            dispatch(new SendSmsJob($user->phone, $message));
        }
    }

    public static function sendEmail(Authenticatable $user, array $data): void
    {
        Mail::to($user->email)->send(new BasicMail($user, $data));
    }

    private static function sendRealtimeNotification(Authenticatable $user, array $data): void
    {
        if (config('project.realtime.enabled')) {
            event(new NotificationEvent($user->id, $data));
        }
    }

    private static function resolveMessageContent(array $data): string
    {
        $message = transWithParams($data['msg'], 'notifications.sms').PHP_EOL;

        if (isset($data['urlText'])) {
            $message .= $data['urlText'].PHP_EOL;
        }

        if (isset($data['url'])) {
            $message .= url($data['url']);
        }

        return $message;
    }
}
