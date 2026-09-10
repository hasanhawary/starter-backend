<?php

namespace Modules\Form\app\Http\Resources\Admin;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translation_name' => $this->getTranslations('name'),
            'sorting_order' => $this->sorting_order,
            'form' => $this->whenLoaded('form', fn () => new FormResource($this->form), ['id' => $this->form_id]),
            'fields' => $this->whenLoaded('fields', fn () => FormFieldResource::collection($this->fields), []),
            'submission_values' => $this->whenLoaded('submissionValues', fn () => FormSubmissionValueResource::collection($this->submissionValues), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
        ];
    }
}
