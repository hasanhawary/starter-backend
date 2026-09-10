<?php

namespace Modules\Notification\app\Tools\Contracts;

use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Models\SystemEvent;
use Throwable;

interface SystemEventServiceInterface
{
    /**
     * Update a system event with new data including variables and model relations.
     *
     * @throws Throwable
     */
    public function updateSystemEvent(SystemEvent $systemEvent, array $data): SystemEvent;

    /**
     * Find a system event by its slug.
     */
    public function find(SystemEventSlugEnum $eventName): ?SystemEvent;
}
