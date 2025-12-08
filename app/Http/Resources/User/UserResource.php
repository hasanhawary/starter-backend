<?php

namespace App\Http\Resources\User;

use App\Enum\User\UserGenderEnum;
use App\Http\Resources\DataEntry\CountryResource;
use App\Http\Resources\Global\Other\BasicResource;
use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => [
                'phone_code_id' => $this->phone_code_id,
                'phone' => $this->phone,
            ],
            'gender' => $this->gender,
            'display_gender' => UserGenderEnum::resolve($this->gender),
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,
            'roles' => $this->whenLoaded('roles', fn() => RoleResource::collection($this->roles), []),
            'creator' => $this->whenLoaded('creator', fn() => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'locations' => $this->whenLoaded('locations', fn() => BasicResource::collection($this->locations), []),
            'nationality' => $this->whenLoaded('nationality', fn() => new CountryResource($this->nationality), ['id' => $this->nationality_id]),
            'created_at' => $this->created_at,
        ];
    }
}
