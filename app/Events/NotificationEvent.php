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
        app()->setLocale('ar');  // Get It From user setting in future
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new Channel("notification.user.$this->user_id")];
    }

    public function broadcastWith(): array
    {
        return [
            'target_id' => $this->data['target_id'] ?? null,
            'target_type' => $this->data['target_type'] ?? null,
            'url' => $this->data['url'] ?? null,
            'title' => ! empty($this->data['title']) ? transWithParams($this->data['title'], 'notifications.realtime') : '',
            'message' => ! empty($this->data['msg']) ? transWithParams($this->data['msg'], 'notifications.realtime') : '',
            'created_at' => now(),
        ];
    }
}
