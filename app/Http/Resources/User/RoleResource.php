<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translation_display_name' => $this->display_name,
            'display_name' => $this->getTranslations('display_name'),
            'permissions' => $this->whenLoaded('permissions', fn() => PermissionResource::collection($this->permissions), []),
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
