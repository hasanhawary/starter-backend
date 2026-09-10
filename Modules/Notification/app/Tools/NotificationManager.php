<?php

namespace Modules\Notification\app\Tools;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Tools\Contracts\NotificationServiceInterface;
use Modules\Notification\Tools\Services\Notification\NotificationEventService;
use Modules\Notification\Tools\Services\Notification\SystemEventService;
use Throwable;

class NotificationManager implements NotificationServiceInterface
{
    /**
     * Channels to send through. Null = all channels.
     */
    public function __construct(
        protected SystemEventService $eventSystemService,
        protected NotificationEventService $notificationEventService,
    ) {}

    /**
     * Create a new notification event with templates, variables, and recipients.
     *
     * Delegates to the NotificationEventService to handle the creation within a transaction.
     *
     *
     * @throws Throwable
     */
    public function storeNotificationEvent(array $data): NotificationEvent
    {
        return $this->notificationEventService->storeNotificationEvent($data);
    }

    /**
     * Update a system event with new data including variables and model relations.
     *
     * Delegates to the EventSystemService to handle the update within a transaction.
     *
     *
     * @throws Throwable
     */
    public function updateSystemEvent(SystemEvent $systemEvent, array $data): SystemEvent
    {
        return $this->eventSystemService->updateSystemEvent($systemEvent, $data);
    }

    /**
     * Update an existing notification event with new data.
     *
     * Delegates to the NotificationEventService to handle the update within a transaction.
     *
     *
     * @throws Throwable
     */
    public function updateNotificationEvent(NotificationEvent $notificationEvent, array $data): NotificationEvent
    {
        return $this->notificationEventService->updateNotificationEvent($notificationEvent, $data);
    }

    /**
     * Update reminder settings for a notification event.
     *
     * Delegates to the NotificationEventService to handle reminder settings update.
     *
     *
     * @throws Throwable
     */
    public function updateReminderSettings(NotificationEvent $notificationEvent, array $reminderSettings): NotificationEvent
    {
        return $this->notificationEventService->updateReminderSettings($notificationEvent, $reminderSettings);
    }

    /**
     * Send notifications for a specific event.
     *
     * Triggers the notification sending process for all recipients associated with the given event name.
     * This method delegates to the NotificationEventService to handle the actual sending logic.
     */
    public function send(string $eventName, Model $model): NotificationServiceInterface
    {
        $this->notificationEventService->send($eventName, $model);

        return $this;
    }

    public function scheduled(Collection $events): NotificationServiceInterface
    {
        $this->notificationEventService->sendScheduledEvents($events);

        return $this;
    }
}
