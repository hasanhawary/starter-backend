<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $user_id, public array $data)
    {
        app()->setLocale('ar');  // Set locale to Arabic
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [new Channel("notification.user.$this->user_id")];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notification.event';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'type' => $this->data['type'] ?? null,
            'title' => !empty($this->data['title']) ? transWithParams($this->data['title']) : '',
            'message' => !empty($this->data['msg']) ? transWithParams($this->data['msg']) : '',
            'created_at' => now()
        ];
    }
}
