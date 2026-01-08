<?php

namespace App\Http\Resources\Central\Tenant;

use App\Http\Resources\Central\Global\Other\BasicResource;
use App\Http\Resources\Central\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => [
                'phone' => $this->phone,
                'phone_code' => $this->whenLoaded('phoneCode', fn() => $this->phoneCode?->phone_code, ''),
                'phone_code_id' => $this->phone_code_id,
            ],
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

