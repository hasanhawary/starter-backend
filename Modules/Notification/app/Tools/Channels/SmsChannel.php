<?php

namespace Modules\Notification\app\Tools\Channels;

use Modules\Notification\app\Tools\Factory\SmsFactory;
use Throwable;

/**
 * SMS notification channel
 * Handles sending notifications via SMS
 */
class SmsChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('sms');
    }

    /**
     * Send SMS notification to all users
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
     * Send SMS to a single user.
     *
     * The text comes from $this->body — the copy replaceVariables() already
     * resolved in the reader's language. Reading $template->body instead would
     * send the stored template as-is, placeholders and all.
     *
     * @param  mixed  $user
     * @param  mixed  $template
     *
     * @throws Throwable
     */
    public function sendToUser($user, $template): void
    {
        try {
            SmsFactory::default()->send($user->phone, $this->bodyFor($user)); // reads SMS_GATEWAY

            $this->createLog($user, $template);
        } catch (\Exception $e) {
            // Log failure
            $this->createLog($user, $template, 'failed', $e->getMessage());
        }
    }
}
