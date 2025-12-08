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
    /**
     * @param User $user
     * @param array $data
     * @param array|null $types
     * @return void
     */
    public static function resolve(User $user, array $data, ?array $types = ['notify', 'realtime']): void
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
                info("Error => " . $exception?->getMessage());
            }
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return void
     */
    private static function sendNotify(User $user, array $data): void
    {
        $user->notify(new UserNotify($data));
    }

    /**
     * @param User $user
     * @param array $data
     * @return void
     */
    private static function sendSMS(User $user, array $data): void
    {
        $message = self::resolveMessageContent($data);

        if ($user->phone) {
            dispatch(new SendSmsJob($user->phone, $message));
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return void
     */
    public static function sendEmail(User $user, array $data): void
    {
        Mail::to($user->email)->send(new BasicMail($user, $data));
    }

    /**
     * @param User $user
     * @param array $data
     * @return void
     */
    private static function sendRealtimeNotification(User $user, array $data): void
    {
        if (config('services.realtime.enable')) {
            event(new NotificationEvent($user->id, $data));
        }
    }

    /**
     * @param array $data
     * @return string
     */
    private static function resolveMessageContent(array $data): string
    {
        $message = $data['msg'] . PHP_EOL;

        if (isset($data['urlText'])) {
            $message .= $data['urlText'] . PHP_EOL;
        }

        if (isset($data['url'])) {
            $message .= url($data['url']);
        }

        return $message;
    }
}
