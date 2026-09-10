<?php

namespace Modules\Notification\Tools\Services\Notification;

use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Tools\Contracts\SystemEventServiceInterface;
use Throwable;

class SystemEventService implements SystemEventServiceInterface
{
    /**
     * Update a system event with new data.
     *
     * Synchronizes the event's variables within a database transaction.
     *
     * @throws Throwable
     */
    public function updateSystemEvent(SystemEvent $systemEvent, array $data): SystemEvent
    {
        return DB::transaction(function () use ($systemEvent, $data) {
            $systemEvent->syncVariables($data['variables'] ?? []);

            return $systemEvent;
        });
    }

    public function find(SystemEventSlugEnum $eventName): ?SystemEvent
    {
        return SystemEvent::where('event_slug', $eventName->value)->first();
    }
}
