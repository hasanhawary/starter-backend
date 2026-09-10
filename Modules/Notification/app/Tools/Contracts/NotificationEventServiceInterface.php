<?php

namespace Modules\Notification\app\Tools\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Notification\app\Models\NotificationEvent;
use Throwable;

interface NotificationEventServiceInterface
{
    /**
     * Store a new notification event.
     *
     * @throws Throwable
     */
    public function storeNotificationEvent(array $data): NotificationEvent;

    /**
     * Update an existing notification event.
     *
     * @throws Throwable
     */
    public function updateNotificationEvent(NotificationEvent $notificationEvent, array $data): NotificationEvent;

    /**
     * Update reminder settings for a notification event.
     *
     * @throws Throwable
     */
    public function updateReminderSettings(NotificationEvent $notificationEvent, array $reminderSettings): NotificationEvent;

    /**
     * Queue notifications for a specific event to be sent in the background.
     */
    public function send(string $eventName, Model $model): void;

    /**
     * Send notifications for a specific event synchronously (executed inside the queued job).
     *
     * $onlyNotificationEventId / $onlyChannel narrow the send to a single
     * reminder rule and channel; they are set by the scheduled reminder path only.
     */
    public function sendNow(string $eventName, Model $model, ?int $onlyNotificationEventId = null, ?string $onlyChannel = null): void;
}
