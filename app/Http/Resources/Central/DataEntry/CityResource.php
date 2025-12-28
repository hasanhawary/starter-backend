<?php

namespace App\Http\Resources\Central\DataEntry;

use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;


class CityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'translation_name' => $this->name,
            'description' => $this->getTranslations('description'),
            'translation_description' => $this->description,
            'country_id' => $this->whenLoaded('country', fn() => new BasicUserResource($this->country), ['id' => $this->country_id]),
            'is_active' => $this->is_active,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
