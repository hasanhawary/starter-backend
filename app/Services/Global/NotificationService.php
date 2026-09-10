<?php

namespace App\Services\Global;

use App\Events\NotificationEvent;
use App\Jobs\SendSmsJob;
use App\Mail\BasicMail;
use App\Models\User;
use App\Notifications\UserNotify;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function resolve(User $user, array $data, ?array $types = ['notify', 'realtime']): void
    {
        foreach ($types as $type) {
            try {
                // Respect the global notification master switches (Settings > Notifications).
                if (! notificationChannelEnabled($type)) {
                    continue;
                }

                match ($type) {
                    'realtime' => self::sendRealtimeNotification($user, $data),
                    'notify' => self::sendNotify($user, $data),
                    'email' => self::sendEmail($user, $data),
                    'sms' => self::sendSMS($user, $data),
                    default => null,
                };

            } catch (\Exception|\Error $exception) {
                info('Error => '.$exception?->getMessage());
            }
        }
    }

    private static function sendNotify(User $user, array $data): void
    {
        $user->notify(new UserNotify($data));
    }

    private static function sendSMS(User $user, array $data): void
    {
        $message = self::resolveMessageContent($data);

        if ($user->phone) {
            dispatch(new SendSmsJob($user->phone, $message));
        }
    }

    public static function sendEmail(User $user, array $data): void
    {
        // TODO::HANDLE_IN_SETTING;
        Mail::to($user->email)
            ->locale('ar')
            ->send(new BasicMail($user, $data));
    }

    private static function sendRealtimeNotification(User $user, array $data): void
    {
        if (config('services.realtime.enable')) {
            event(new NotificationEvent($user->id, $data));
        }
    }

    private static function resolveMessageContent(array $data): string
    {
        $message = $data['msg'].PHP_EOL;

        if (isset($data['urlText'])) {
            $message .= $data['urlText'].PHP_EOL;
        }

        if (isset($data['url'])) {
            $message .= url($data['url']);
        }

        return $message;
    }
}
