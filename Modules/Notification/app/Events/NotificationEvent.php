<?php

namespace Modules\Notification\app\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent
{
    use Dispatchable, SerializesModels;

    public string $eventName;

    public ?Model $model;

    public function __construct(string $eventName, Model $model)
    {
        $this->eventName = $eventName;
        $this->model = $model;
    }
}
