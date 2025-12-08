<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * @var string|null
     */
    private ?string $token;

    public function __construct(User $resource, ?string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'avatar' => $this->avatar,
                'permissions' => $this->roles?->flatMap(function ($role) {
                    return $role->permissions->pluck('name');
                })->unique()->values()->toArray(),
                'roles' => $this->roles?->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name
                    ];
                }),
            ],
        ];
    }
}
