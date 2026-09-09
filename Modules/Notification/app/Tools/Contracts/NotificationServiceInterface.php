<?php

namespace Modules\Notification\app\Tools\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\SystemEvent;
use Throwable;

interface NotificationServiceInterface
{
    /**
     * Create a new notification event with templates, variables, and recipients.
     *
     * @throws Throwable
     */
    public function storeNotificationEvent(array $data): NotificationEvent;

    /**
     * Update a system event with new data including variables and model relations.
     *
     * @throws Throwable
     */
    public function updateSystemEvent(SystemEvent $systemEvent, array $data): SystemEvent;

    /**
     * Update an existing notification event with new data.
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
     * Send notifications for a specific event.
     */
    public function send(string $eventName, Model $model): self;

    /**
     * Send scheduled notifications
     */
    public function scheduled(Collection $events): self;
}
