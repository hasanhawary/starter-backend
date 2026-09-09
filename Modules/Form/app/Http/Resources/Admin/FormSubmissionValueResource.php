<?php

namespace Modules\Form\app\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Form\Tools\Form\Services\FormReferenceResolver;

class FormSubmissionValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field' => $this->whenLoaded('field', fn () => new FormFieldResource($this->field)),
            'step' => $this->whenLoaded('step', fn () => new FormStepResource($this->step)),
            'value' => $this->value,
            'replaced_value' => $this->whenLoaded(
                'field',
                fn () => app(FormReferenceResolver::class)->resolve($this->field, $this->value)
            ),
            'created_at' => $this->created_at,
        ];
    }
}
