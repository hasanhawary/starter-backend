<?php

namespace App\Http\Resources\Central\Subscription;

use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanFeatureResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'key' => $this->key,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'value' => $this->value,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
