<?php

namespace Modules\Showcase\app\Http\Resources;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowcaseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),
            'code' => $this->code,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
            ], ['id' => $this->parent_id]),
            'children' => $this->whenLoaded('children', fn () => self::collection($this->children), []),
            'showcases_count' => $this->whenCounted('showcases'),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
