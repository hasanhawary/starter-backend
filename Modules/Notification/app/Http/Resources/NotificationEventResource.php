<?php

namespace Modules\Notification\app\Http\Resources;

use BackedEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\app\Enum\NotificationChannelEnum;

class NotificationEventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'system_event_id' => $this->system_event_id,
            'name' => $this->name,
            'translation_name' => $this->getTranslations('name'),
            'is_reminder' => $this->is_reminder,
            'type' => $this->type instanceof BackedEnum ? $this->type->value : $this->type,
            'variables' => $this->whenLoaded('variables', fn () => VariableResource::collection($this->variables), []),
            'templates' => $this->whenLoaded('templates', fn () => NotificationEventTemplateResource::collection($this->templates), []),
            'channels' => $this->whenLoaded('templates', function () {
                return $this->templates->pluck('channel')->unique()->values()->map(fn ($channel) => [
                    'channel' => $channel,
                    'display_channel' => NotificationChannelEnum::resolve($channel),
                ]);
            }, []),
            'reminder_setting' => $this->whenLoaded('remindersSetting', fn () => ReminderSettingResource::groupCollection($this->remindersSetting)),
            'notification_recipients' => $this->whenLoaded('notificationRecipients', fn () => NotificationRecipientResource::collection($this->notificationRecipients)),
            'created_at' => $this->created_at,
        ];
    }
}
