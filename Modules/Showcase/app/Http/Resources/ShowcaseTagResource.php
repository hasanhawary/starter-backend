<?php

namespace Modules\Showcase\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowcaseTagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'slug' => $this->slug,
            'color' => $this->color,
            'is_active' => $this->is_active,
            // Present only when the tag was read through the pivot.
            'is_primary' => $this->whenPivotLoaded('showcase_showcase_tag', fn () => (bool) $this->pivot->is_primary),
        ];
    }
}
