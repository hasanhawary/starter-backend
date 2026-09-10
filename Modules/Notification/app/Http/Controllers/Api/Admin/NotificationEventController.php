<?php

namespace Modules\Notification\app\Http\Controllers\Api\Admin;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Requests\Global\Other\PageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Gate;
use Modules\Notification\app\Http\Requests\NotificationEventRequest;
use Modules\Notification\app\Http\Requests\UpdateReminderRequest;
use Modules\Notification\app\Http\Resources\NotificationEventResource;
use Modules\Notification\app\Http\Resources\ReminderSettingResource;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Tools\Facades\Notification;
use Throwable;

class NotificationEventController
{
    public function index(PageRequest $request, $system_event_id): JsonResponse
    {
        Gate::authorize('read-notification-event');

        $query = app(Pipeline::class)
            ->send(NotificationEvent::with(['variables', 'templates', 'remindersSetting.verifiableDate', 'notificationRecipients'])->where('system_event_id', $system_event_id))
            ->through([ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, NotificationEventResource::class));
    }

    /**
     * @throws Throwable
     */
    public function store(NotificationEventRequest $request): JsonResponse
    {
        Gate::authorize('create-notification-event');

        $notificationEvent = Notification::storeNotificationEvent($request->validated());

        return successResponse(
            new NotificationEventResource($notificationEvent->load(['variables', 'notificationRecipients', 'templates', 'remindersSetting', 'remindersSetting.verifiableDate'])),
            __('api.created_success')
        );
    }

    public function show(NotificationEvent $notificationEvent): JsonResponse
    {
        Gate::authorize('read-notification-event');

        return successResponse(new NotificationEventResource($notificationEvent->load(['variables', 'notificationRecipients', 'templates', 'remindersSetting', 'remindersSetting.verifiableDate'])));
    }

    /**
     * @throws Throwable
     */
    public function update(NotificationEventRequest $request, NotificationEvent $notificationEvent): JsonResponse
    {
        Gate::authorize('update-notification-event');

        $notificationEvent = Notification::updateNotificationEvent($notificationEvent, $request->validated());

        return successResponse(
            new NotificationEventResource($notificationEvent->load(['variables', 'notificationRecipients', 'templates', 'remindersSetting.verifiableDate'])),
            __('api.updated_success')
        );
    }

    public function destroy(NotificationEvent $notificationEvent): JsonResponse
    {
        Gate::authorize('delete-notification-event');

        $notificationEvent->delete();

        return successResponse(msg: __('api.deleted_success'));
    }

    /**
     * @throws Throwable
     */
    public function updateReminderSettings(UpdateReminderRequest $request, NotificationEvent $notificationEvent): JsonResponse
    {
        Gate::authorize('update-reminders-setting');

        $notificationEvent = Notification::updateReminderSettings($notificationEvent, $request->validated());

        return successResponse(
            ReminderSettingResource::groupCollection($notificationEvent->load('remindersSetting.verifiableDate')->remindersSetting),
            msg: __('api.updated_success')
        );
    }
}
