<?php

namespace App\Http\Resources\User;

use App\Enum\User\UserGenderEnum;
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
                'phone' => $this->phone,
                'phone_code' => $this->whenLoaded('phoneCode', fn () => $this->phoneCode?->phone_code, ''),
                'phone_code_id' => $this->phone_code_id,
            ],
            'gender' => $this->gender,
            'display_gender' => UserGenderEnum::resolve($this->gender),
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,
            'roles' => $this->whenLoaded('roles', fn () => BasicResource::collection($this->roles), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'settings' => $this->whenLoaded('settings', fn () => new UserSettingResource($this->settings), ['id' => null]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
