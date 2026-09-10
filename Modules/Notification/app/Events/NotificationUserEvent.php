<?php

namespace Modules\Notification\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationUserEvent
{
    use Dispatchable, SerializesModels;

    public string $eventName;

    public array $data;

    public function __construct(string $eventName, array $data = [])
    {
        $this->eventName = $eventName;
        $this->data = $data;
    }
}
