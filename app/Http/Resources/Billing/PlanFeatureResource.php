<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Global\Other\BasicUserResource;

class PlanFeatureResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'feature_key' => $this->feature_key,
            'value' => $this->value,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
