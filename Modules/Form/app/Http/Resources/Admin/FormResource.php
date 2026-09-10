<?php

namespace Modules\Form\app\Http\Resources\Admin;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translation_name' => $this->getTranslations('name'),
            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),
            'has_steps' => $this->has_steps,
            'fields' => $this->when(! $this->has_steps, fn () => $this->whenLoaded('fields', fn () => FormFieldResource::collection($this->fields), []), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'steps' => $this->when($this->has_steps, fn () => $this->whenLoaded('steps', fn () => FormStepResource::collection($this->steps), []), []),
            'created_at' => $this->created_at,
        ];
    }
}
