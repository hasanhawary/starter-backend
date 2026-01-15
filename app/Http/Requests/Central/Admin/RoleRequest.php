<?php

namespace App\Http\Requests\Central\Admin;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Central\Admin\RoleResource;
use App\Models\Central\Role;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;

class RoleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('role')?->getKey();

        return [
            'name' => [
                'required',
                Rule::unique('roles', 'name')->ignore($roleId),
                new UniqueCheck(
                    Role::class,
                    RoleResource::class,
                    $roleId
                ),
            ],

            'display_name' => [
                'required',
                'array',
                new TranslatableRequired('roles', ['string', 'max:191'], 'country')
            ],

            'permissions' => [
                'required',
                'array',
                'min:1',
            ],

            'permissions.*' => [
                'required',
                Rule::exists('permissions', 'name'),
            ],

            'guard_name' => [
                'required', 'string'
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'guard_name' => detectPermissionGuard(),
            'permissions' => array_values(array_unique(array_merge($this->permissions ?? [], config('roles.default.permissions'))))
        ]);
    }
}
