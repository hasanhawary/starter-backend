<?php

namespace App\Http\Resources\Central\Subscription;

use App\Enum\Subscription\PlanBillingCycleEnum;
use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'is_active' => $this->is_active,
            'features' => $this->whenLoaded('features', fn() => PlanFeatureResource::collection($this->features), []),
            'prices' => $this->whenLoaded('prices', fn() => PlanPriceResource::collection($this->prices), []),
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
