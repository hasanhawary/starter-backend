<?php

namespace Modules\Form\app\Http\Resources\Admin;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->whenLoaded('source', fn () => $this->source),
            'submission' => $this->whenLoaded('submission', fn () => new BasicUserResource($this->submission)),
            'values' => $this->whenLoaded('values', fn () => FormSubmissionValueResource::collection($this->values), []),
            'created_at' => $this->created_at,
        ];
    }
}
