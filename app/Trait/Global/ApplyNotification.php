<?php

namespace App\Trait\Global;

use App\Services\Global\NotificationService;

trait ApplyNotification
{
    /**
     * @param array $data
     * @param array|null $types
     * @return void
     */
    public function sendNotification(array $data, ?array $types = ['notify', 'realtime']): void
    {
        NotificationService::resolve($this, $data, $types);
    }
}
