<?php

namespace App\Http\Resources\Central\Tenant;

use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'domain' => $this->domain,
            'database' => $this->database,
            'is_active' => $this->is_active,
            'settings' => $this->settings ?? [],
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
