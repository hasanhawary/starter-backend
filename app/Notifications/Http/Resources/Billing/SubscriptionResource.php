<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Billing\PlanResource;
use App\Enum\Billing\SubscriptionStatusEnum;
use App\Http\Resources\Global\Other\BasicUserResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'plan_id' => $this->plan_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'display_status' => SubscriptionStatusEnum::resolve($this->status),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
