<?php

namespace App\Http\Resources\Central\Global\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'target_id' => @$this->data['target_id'],
            'target_type' => @$this->data['target_type'],
            'url' => @$this->data['url'],
            'title'   => strip_tags(emailTrans($this->data['title'] ?? '')),
            'message' => strip_tags(emailTrans($this->data['message'] ?? '')),
            'read_at' => $this->read_at,
            'open_at' => $this->open_at,
            'created_at' => $this->created_at,
        ];
    }
}
