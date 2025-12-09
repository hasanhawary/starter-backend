<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Global\Other\BasicUserResource;


class StageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'translation_name' => $this->name,
            'description' => $this->getTranslations('description'),
            'translation_description' => $this->description,
            'slug' => $this->slug,
            'order' => $this->order,
            'is_final' => $this->is_final,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
