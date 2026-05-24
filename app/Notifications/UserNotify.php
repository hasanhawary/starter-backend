<?php

namespace App\Notifications;

use App\Enum\Global\NotificationGroupEnum;
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

    public function toDatabase(): array
    {
        return [
            'target_id' => $this->data['target_id'] ?? '',
            'target_type' => $this->data['target_type'] ?? '',
            'group' => $this->data['group'] ?? NotificationGroupEnum::Global->value,
            'title' => $this->data['title'] ?? '',
            'message' => $this->data['msg'] ?? '',
        ];
    }
}
