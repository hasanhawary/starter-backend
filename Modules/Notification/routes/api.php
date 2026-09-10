<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\app\Http\Controllers\Api\Admin\CalendarController;
use Modules\Notification\app\Http\Controllers\Api\Admin\NotificationController;
use Modules\Notification\app\Http\Controllers\Api\Admin\NotificationEventController;
use Modules\Notification\app\Http\Controllers\Api\Admin\ReminderController;
use Modules\Notification\app\Http\Controllers\Api\Admin\SystemEventController;

/*
|--------------------------------------------------------------------------
| notifications Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // ----------- System Events Routes --------------
    Route::get('{module}/system-events', [SystemEventController::class, 'index']);
    Route::get('system-events/{module}', [SystemEventController::class, 'index']);
    Route::put('system-events/{module}/{systemEvent}', [SystemEventController::class, 'update']);
    Route::get('system-events-receivers/{module?}', [SystemEventController::class, 'receivers']);
    Route::get('system-events-variables/{systemEvent}', [SystemEventController::class, 'variables']);
    Route::get('system-events-verifiable-dates/{module?}', [SystemEventController::class, 'verifiableDates']);

    // ----------- Notification Events Routes --------------
    Route::put('notification-events/{notification_event}/reminder-setting', [NotificationEventController::class, 'updateReminderSettings']);
    Route::apiResource('notification-events', NotificationEventController::class);
    // /Route::get('notification-events/{system_event_id}', [NotificationEventController::class, 'index']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications', [NotificationController::class, 'update']);

    // ----------- Schedule Events Routes --------------
    Route::get('schedule-events/reminder', [ReminderController::class, 'index']);
    Route::get('schedule-events/calendar', [CalendarController::class, 'index']);
    Route::get('schedule-events/calendar/{month}', [CalendarController::class, 'getByMonth']);
});
