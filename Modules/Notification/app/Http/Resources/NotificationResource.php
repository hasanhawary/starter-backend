<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'title' => $this->title,
            'body' => $this->body,
            'meta' => $this->meta,
            'source' => $this->resolveSource(),
            'status' => $this->status,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'open_at' => $this->open_at,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Decode the triggering model stored on the notification (meta column,
     * with a data.source fallback) into a structured array.
     *
     * @return array{id: mixed, type: string, type_path: string}|null
     */
    private function resolveSource(): ?array
    {
        $meta = is_string($this->meta) ? json_decode($this->meta, true) : $this->meta;

        if (! empty($meta)) {
            return $meta;
        }

        $data = is_string($this->data) ? json_decode($this->data, true) : $this->data;

        return $data['source'] ?? null;
    }
}
