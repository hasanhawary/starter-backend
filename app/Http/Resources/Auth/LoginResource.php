<?php

namespace App\Http\Resources\Auth;

use App\Services\Global\EncryptionService;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Random\RandomException;

class LoginResource extends JsonResource
{
    private ?string $token;

    public function __construct(User $resource, ?string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    /**
     * @throws \JsonException
     * @throws RandomException
     */
    public function toArray(Request $request): array
    {
        $permissions = $this->roles
            ?->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->toArray();

        $roles = $this->roles?->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
        ])->toArray();

        $encryptRole = config('project.auth.encryption.outgoing.roles', true);
        $encryptPermission = config('project.auth.encryption.outgoing.permissions', true);

        return [
            'token' => $this->token,
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'avatar' => $this->avatar,
                'roles' => $encryptRole ? EncryptionService::encrypt($roles) : $roles,
                'permissions' => $encryptPermission ? EncryptionService::encrypt($permissions) : $permissions,
            ],
        ];
    }
}
