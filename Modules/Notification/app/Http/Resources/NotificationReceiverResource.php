<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationReceiverResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translation_name' => $this->getTranslations('name'),
            'module' => $this->module,
            'relation' => $this->relation,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }
}
