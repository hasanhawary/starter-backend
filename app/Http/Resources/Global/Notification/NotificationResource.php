<?php

namespace App\Http\Resources\Global\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => @$this->data['id'],
            'type' => @$this->data['type'],
            'url' => @$this->data['url'],
            'title' => parseKeyValueString(@$this->data['title']),
            'message' => parseKeyValueString(@$this->data['message']),
            'read_at' => $this->read_at,
            'open_at' => $this->open_at,
            'created_at' => $this->created_at,
        ];
    }
}
