<?php

namespace App\Trait\Global;

use App\Services\Global\NotificationService;

trait ApplyNotification
{
    public function sendNotification(array $data, ?array $types = ['notify', 'realtime']): void
    {
        NotificationService::resolve($this, $data, $types);
    }
}
