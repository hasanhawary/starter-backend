<?php

namespace App\Http\Resources\Central\Subscription;

use App\Enum\Subscription\PlanBillingCycleEnum;
use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cycle' => $this->cycle,
            'display_cycle' => PlanBillingCycleEnum::resolve($this->cycle),
            'price' => $this->price,
            'currency' => $this->currency,
            'formatted_price' => $this->getFormattedPrice(),
            'discount_percent' => $this->discount_percent,
            'discounted_price' => $this->getDiscountedPrice(),
            'formatted_discounted_price' => $this->discount_percent ? $this->getFormattedDiscountedPrice() : null,
            'savings' => $this->discount_percent ? $this->price - $this->getDiscountedPrice() : 0,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'plan_id' => $this->plan_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
