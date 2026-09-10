<?php

namespace Modules\Notification\app\Tools\Channels;

use App\Models\User;
use Modules\Notification\app\Enum\ScheduleEventTypeEnum;
use Modules\Notification\app\Models\ReminderLog;
use Modules\Notification\app\Models\ScheduleEvent;
use Throwable;

class ReminderChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('reminder');
    }

    /**
     * @throws Throwable
     */
    public function send($template, $notificationEvent): bool
    {
        $this->notificationEvent = $notificationEvent;

        foreach ($this->users as $user) {
            $this->sendToUser($user, $template);
        }

        return true;
    }

    /**
     * Record the reminder as a schedule event the dashboard can list.
     *
     * Wrapped so a failure still leaves a trace: without it the exception
     * escapes to the service, which logs it to the application log only, and
     * notification_logs shows nothing at all for the attempt.
     *
     * @throws Throwable
     */
    public function sendToUser($user, $template): void
    {
        try {
            $this->createScheduleEvent($user, $template);

            $this->createLog($user, $template);
        } catch (\Exception $e) {
            $this->createLog($user, $template, 'failed', $e->getMessage());
        }
    }

    private function createScheduleEvent($user, $template): void
    {
        $scheduleEvent = ScheduleEvent::create([
            'type' => ScheduleEventTypeEnum::Reminder,
            'title' => $this->title,
            'body' => $this->body,
            'date_time' => $this->resolveDateTime($template->date_column),
            'notification_event_id' => $this->notificationEvent->id,
            'status' => 'sent',
            'receiver_id' => $user->id,
            'receiver_type' => User::class,
            'source_type' => $this->model ? get_class($this->model) : null,
            'source_id' => $this->model?->getKey(),
        ]);

        $scheduleEvent->scheduleEventReceivers()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);

        ReminderLog::create([
            'schedule_event_id' => $scheduleEvent->id,
            'notification_event_id' => $this->notificationEvent->id,
            'user_id' => $user->id,
            'channel' => 'reminder',
            'payload' => ['title' => $this->title, 'body' => $this->body],
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
