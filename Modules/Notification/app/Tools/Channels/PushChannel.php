<?php

namespace Modules\Notification\app\Tools\Channels;

use Modules\Notification\app\Events\PushNotificationEvent;
use Throwable;

class PushChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('push');
    }

    /**
     * @throws Throwable
     */
    public function send($template, $notificationEvent): bool
    {
        $this->notificationEvent = $notificationEvent;

        foreach ($this->users as $user) {
            $this->sendToUser($user, $template);
        }

        return true;
    }

    /**
     * Broadcast a push notification to the user over Reverb.
     *
     * @throws Throwable
     */
    public function sendToUser($user, $template): void
    {
        try {
            event(new PushNotificationEvent($user->id, [
                'id' => $this->notificationEvent->id,
                'title' => $this->titleFor($user),
                'body' => $this->bodyFor($user),
                'created_at' => now()->toIso8601String(),
            ]));

            $this->createLog($user, $template);
        } catch (\Exception $e) {
            $this->createLog($user, $template, 'failed', $e->getMessage());
        }
    }
}
