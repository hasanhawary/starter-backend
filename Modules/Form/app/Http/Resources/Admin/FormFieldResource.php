<?php

namespace Modules\Form\app\Http\Resources\Admin;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonException;

class FormFieldResource extends JsonResource
{
    /**
     * @throws JsonException
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translation_name' => $this->getTranslations('name'),
            'scheme' => $this->scheme,
            'step' => $this->whenLoaded('step', fn () => new FormStepResource($this->step), ['id' => $this->form_step_id]),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
        ];
    }
}
