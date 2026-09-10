<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->getTranslations('name'),
            'name' => $this->name,
            'model_type' => $this->model_type,
            'access_key' => $this->access_key,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }
}
