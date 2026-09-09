<?php

namespace Modules\Notification\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Tools\Services\Notification\NotificationEventService;
use Throwable;

/**
 * Dispatches a notification event to all its channels in the background.
 *
 * The triggering model is serialized by reference (class + id) and re-fetched
 * fresh when the worker runs, keeping the queue payload small.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying a failed attempt.
     */
    public int $backoff = 10;

    /**
     * $notificationEventId / $channel are set by the scheduled reminder path only:
     * they narrow the send to a single reminder rule and its configured channel,
     * instead of every instant rule of the system event.
     */
    public function __construct(
        public string $eventName,
        public Model $model,
        public ?int $notificationEventId = null,
        public ?string $channel = null,
    ) {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    public function handle(NotificationEventService $service): void
    {
        $service->sendNow($this->eventName, $this->model, $this->notificationEventId, $this->channel);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("SendNotificationJob failed for event '{$this->eventName}': ".$exception->getMessage());
    }
}
