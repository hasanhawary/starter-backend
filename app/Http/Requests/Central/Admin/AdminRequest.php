<?php

namespace App\Http\Requests\Central\Admin;

use App\Enum\User\UserGenderEnum;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Models\Central\Admin;
use App\Models\Central\Country;
use App\Rules\UniqueWithTrashed;
use App\Rules\ValidLength;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class AdminRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $adminId = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:50'],

            'phone_code_id' => [
                'nullable',
                'numeric',
                Rule::exists('countries', 'id')->withoutTrashed(),
            ],

            'phone' => [
                'nullable',
                'regex:/^[0-9]+$/',
                new ValidLength($this->input('phone_code_id'), Country::class, 'phone_length'),
            ],

            'email' => [
                'required',
                'email',
                Rule::unique('admins', 'email')->ignore($adminId)->withoutTrashed(),
                new UniqueWithTrashed(
                    Admin::class,
                    AdminResource::class,
                    $adminId
                ),
            ],

            'password' => [
                'required',
                'confirmed',
                'min:8'
            ],

            'gender' => ['required', new Enum(UserGenderEnum::class)],
            'avatar' => ['sometimes', 'nullable', File::image()->max(20048)], // 20MB max
            'roles' => [
                'required',
                'array',
                'min:1',
                Rule::exists('roles', 'id')->whereNot('name', 'root'),
            ],

            'is_active' => ['sometimes', 'boolean'],

            'permissions' => ['sometimes', 'nullable', 'array', 'distinct', 'min:1'],
            'permissions.*' => [
                'required_with:permissions',
                Rule::exists('permissions', 'id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $phone = $this->input('phone');

        if (is_array($phone)) {
            $this->merge([
                'phone' => $phone['phone'] ?? null,
                'phone_code_id' => $phone['phone_code_id'] ?? null,
            ]);
        }

        $roles = $this->input('roles', []);
        if (!is_array($roles)) {
            $this->merge(['roles' => Arr::wrap($roles)]);
        }
    }
}
