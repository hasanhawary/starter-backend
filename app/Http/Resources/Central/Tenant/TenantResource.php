<?php

namespace App\Http\Resources\Central\Tenant;

use App\Enum\Tenant\TenantStatusEnum;
use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'database' => $this->database,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'display_status' => TenantStatusEnum::resolve($this->status),
            'settings' => $this->settings ?? [],
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
