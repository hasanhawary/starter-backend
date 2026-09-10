<?php

namespace Modules\Notification\app\Http\Controllers\Api\Admin;

use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Http\Requests\Global\Other\PageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;
use Modules\Notification\app\Http\Requests\SystemEventRequest;
use Modules\Notification\app\Http\Resources\NotificationVerifiableDateResource;
use Modules\Notification\app\Http\Resources\ReceiversResource;
use Modules\Notification\app\Http\Resources\SystemEventResource;
use Modules\Notification\app\Http\Resources\VariableResource;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationVerifiableDate;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;
use Modules\Notification\app\Tools\Facades\Notification;
use Throwable;

class SystemEventController
{
    public function index(PageRequest $request, $module = null): JsonResponse
    {
        Gate::authorize('read-system-event');

        $query = app(Pipeline::class)
            ->send(SystemEvent::with(['notificationEvents', 'notificationEvents.variables', 'notificationEvents.remindersSetting', 'notificationEvents.notificationRecipients', 'notificationEvents.templates:id,notification_event_id,channel'])->where('module', $module))
            ->through([ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SystemEventResource::class));
    }

    public function show(SystemEvent $systemEvent): JsonResponse
    {
        Gate::authorize('read-system-event');

        $systemEvent->load(['notificationEvents.templates', 'notificationEvents.notificationRecipients', 'notificationEvents.variables']);

        return successResponse(new SystemEventResource($systemEvent));
    }

    /**
     * @throws Throwable
     */
    public function update(SystemEventRequest $request, SystemEvent $systemEvent): JsonResponse
    {
        Gate::authorize('update-system-event');

        Notification::updateSystemEvent($systemEvent, $request->validated());

        return successResponse(
            new SystemEventResource($systemEvent->load(['variables'])),
            __('api.updated_success')
        );
    }

    /**
     * Get the variables assigned to the given system event through the
     * `variable_assignments` pivot.
     */
    public function variables(PageRequest $request, SystemEvent $systemEvent): JsonResponse
    {
        Gate::authorize('read-system-event');

        $query = app(Pipeline::class)
            ->send(
                Variable::query()
                    ->whereHas('systemEvent', fn ($query) => $query->where('variableable_id', $systemEvent->id))
                    ->select('id', 'name')
            )
            ->through([ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, VariableResource::class));
    }

    /**
     * Get available receivers per module from config.
     */
    public function receivers(PageRequest $request, string $module): JsonResponse
    {
        Gate::authorize('read-notification-receiver');

        $query = app(Pipeline::class)
            ->send(NotificationReceiver::where('module', $module))
            ->through([ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, ReceiversResource::class));
    }

    /**
     * Get verifiable date columns per module from DB.
     */
    public function verifiableDates(?string $module = null): JsonResponse
    {
        Gate::authorize('read-notification-verifiable-date');

        $query = NotificationVerifiableDate::active();

        if ($module) {
            $query->module($module);
        }

        return successResponse(NotificationVerifiableDateResource::collection($query->get()));
    }
}
