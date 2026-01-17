<?php

namespace App\Http\Resources\Central\Subscription;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'display_status' => SubscriptionStatusEnum::resolve($this->status),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_cancelled' => $this->isCancelled(),
            'days_remaining' => $this->daysRemaining(),
            'plan' => $this->whenLoaded('plan', fn() => new PlanResource($this->plan), ['id' => $this->plan_id]),
            'tenant' => $this->whenLoaded('tenant', fn() => new BasicUserResource($this->tenant), ['id' => $this->tenant_id]),
            'usages' => $this->whenLoaded('usages', fn() => SubscriptionUsageResource::collection($this->usages), []),
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
