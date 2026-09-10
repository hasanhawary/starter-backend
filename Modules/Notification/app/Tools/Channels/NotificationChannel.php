<?php

namespace Modules\Notification\app\Tools\Channels;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('notification');
    }

    public function send($template, $notificationEvent): bool
    {
        $this->notificationEvent = $notificationEvent;

        foreach ($this->users as $user) {
            $this->sendToUser($user, $template);
        }

        return true;
    }

    public function sendToUser($user, $template): void
    {
        try {
            $title = $this->titleFor($user);
            $body = $this->bodyFor($user);

            $source = $this->sourceData();

            // Use Laravel's standard notifications table
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'system_notification',
                'notification_event_id' => $this->notificationEvent->id,
                'notification_template_id' => $template->id,
                'notifiable_id' => $user->id,
                'notifiable_type' => get_class($user),
                'data' => json_encode([
                    'title' => $this->title,
                    'body' => $this->body,
                    'source' => $source,
                ], JSON_THROW_ON_ERROR),
                'channel' => 'notification',
                'title' => $title,
                'body' => $body,
                'meta' => $source ? json_encode($source, JSON_THROW_ON_ERROR) : null,
                'status' => 'sent',
                'error_message' => null,
                'read_at' => null,
                'open_at' => null,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createLog($user, $template);
        } catch (\Throwable $e) {
            $this->createLog($user, $template, 'failed', $e->getMessage());
        }
    }

    /**
     * The model that triggered the notification, formatted for storage.
     *
     * @return array{id: mixed, type: string, type_path: string}|null
     */
    private function sourceData(): ?array
    {
        if (! isset($this->model)) {
            return null;
        }

        $type = class_basename($this->model);

        return [
            'id' => $this->model->getKey(),
            'type' => $type,
            'type_path' => 'view-'.Str::snake($type),
        ];
    }
}
