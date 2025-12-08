<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserNotify extends Notification
{
    use Queueable;

    public array $data = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * @return array
     */
    public function toDatabase(): array
    {
        return [
            'title' => $this->data['title'] ?? '',
            'message' => $this->data['msg'] ?? '',
            'id' => $this->data['id'] ?? '',
            'type' => $this->data['type'] ?? '',
        ];
    }
}
