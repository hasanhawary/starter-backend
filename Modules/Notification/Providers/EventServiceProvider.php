<?php

namespace Modules\Notification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\app\Events\NotificationEvent;
use Modules\Notification\app\Events\NotificationUserEvent;
use Modules\Notification\app\Events\ScheduleNotificationEvent;
use Modules\Notification\app\Listeners\SendNotificationListener;
use Modules\Notification\app\Listeners\SendNotificationUserListener;
use Modules\Notification\app\Listeners\SendScheduleNotificationListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        NotificationEvent::class => [
            SendNotificationListener::class,
        ],
        NotificationUserEvent::class => [
            SendNotificationUserListener::class,
        ],
        ScheduleNotificationEvent::class => [
            SendScheduleNotificationListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
    }
}
