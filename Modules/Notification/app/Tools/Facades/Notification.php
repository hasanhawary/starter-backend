<?php

namespace Modules\Notification\app\Tools\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Tools\Contracts\NotificationServiceInterface;
use Modules\Notification\app\Tools\NotificationManager;

/**
 * Notification Facade
 *
 * Provides a static interface to the NotificationManager for managing and dispatching
 * notifications throughout the application. This facade simplifies notification event
 * creation, updates, and delivery across multiple channels (email, SMS, push, etc.).
 *
 * @method static NotificationEvent storeNotificationEvent(array $data) Create a new notification event with templates, variables, and recipients. Executes within a database transaction.
 * @method static SystemEvent updateSystemEvent(SystemEvent $systemEvent, array $data) Update an existing system event with new data including variables and model relations. Wrapped in a database transaction.
 * @method static NotificationEvent updateNotificationEvent(NotificationEvent $notificationEvent, array $data) Update an existing notification event with new templates, variables, or recipient configuration. All updates performed atomically.
 * @method static NotificationEvent updateReminderSettings(NotificationEvent $notificationEvent, array $reminderSettings) Update reminder settings for a notification event including intervals, channels, and conditions.
 * @method static NotificationServiceInterface send(string $eventName, Model $model) Send notifications for a specific event to all configured recipients. Supports method chaining.
 * @method static NotificationServiceInterface scheduled(Collection $events) Process and send scheduled notification events in batch. Typically called by scheduled tasks or cron jobs.
 *
 * @see NotificationManager
 */
class Notification extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'notification';
    }
}
