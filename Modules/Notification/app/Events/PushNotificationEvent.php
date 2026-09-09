<?php

namespace Modules\Notification\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts a push notification to a single user over Reverb.
 *
 * Listens on the public "notification.user.{userId}" channel and is
 * received by the frontend under the "notification.push" event name.
 *
 * Broadcast now rather than queued: PushChannel dispatches this from inside
 * SendNotificationJob, so it is already off the request thread, and a queued
 * broadcast would leave the channel logging "sent" for a push that had only
 * been handed to another queue.
 */
class PushNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $userId,
        public array $payload,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("notification.user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.push';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
