<?php

namespace Modules\Notification\app\Tools\Channels;

use Illuminate\Support\Facades\Mail;
use Modules\Notification\app\Mail\NotificationMail;
use Throwable;

/**
 * Email notification channel
 * Handles sending notifications via email (SMTP / Mailtrap).
 */
class EmailChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('email');
    }

    /**
     * Send email notification to all users
     *
     * @param  mixed  $template
     * @param  mixed  $notificationEvent
     *
     * @throws Throwable
     */
    public function send($template, $notificationEvent): bool
    {
        $this->notificationEvent = $notificationEvent;

        if (! $this->checkEnabled()) {
            return false;
        }

        foreach ($this->users as $user) {
            $this->sendToUser($user, $template);
        }

        return true;
    }

    /**
     * Send email to a single user
     *
     * @param  mixed  $user
     * @param  mixed  $template
     */
    public function sendToUser($user, $template): void
    {
        try {
            Mail::to($user->email)->send(
                new NotificationMail(
                    subjectLine: $this->titleFor($user),
                    body: $this->bodyFor($user),
                    lang: $this->localeFor($user),
                )
            );

            $this->createLog($user, $template);
        } catch (\Exception $e) {
            $this->createLog($user, $template, 'failed', $e->getMessage());
        }
    }
}
