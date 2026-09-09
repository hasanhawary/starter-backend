<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReceiversResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->getTranslations('name'),
            'name' => $this->name,
            'created_at' => $this->created_at,
        ];
    }
}
