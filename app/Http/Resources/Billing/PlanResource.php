<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Enum\Billing\PlanBillingCycleEnum;
use App\Http\Resources\Global\Other\BasicUserResource;

class PlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle,
            'display_billing_cycle' => PlanBillingCycleEnum::resolve($this->billing_cycle),
            'max_users' => $this->max_users,
            'max_storage_mb' => $this->max_storage_mb,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
