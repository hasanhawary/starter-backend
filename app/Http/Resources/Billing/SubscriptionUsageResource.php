<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Global\Other\BasicUserResource;

class SubscriptionUsageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'feature_key' => $this->feature_key,
            'used_value' => $this->used_value,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
