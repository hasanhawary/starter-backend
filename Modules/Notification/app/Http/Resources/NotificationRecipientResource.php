<?php

namespace Modules\Notification\app\Http\Resources;

use App\Http\Resources\User\RoleResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notification\app\Models\NotificationRecipient;

class NotificationRecipientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'notification_event_id' => $this->notification_event_id,
            'recipient_type' => $this->recipientable_type,
            'recipientable' => [
                'id' => $this->recipientable?->id,
                'name' => $this->recipientable?->name ?? $this->recipientable?->first_name,
            ],
            'recipient_id' => $this->recipientable_id,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }


}
