<?php

namespace Modules\Notification\app\Tools\Contracts;

interface SmsServiceInterface
{
    public function send(string $to, string $message): bool;
}
