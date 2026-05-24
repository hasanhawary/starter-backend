<?php

namespace App\Http\Requests\User;

use App\Enum\User\UserGenderEnum;
use App\Http\Requests\BaseFormRequest;
use App\Models\Country;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\UniqueCheck;
use App\Rules\ValidLength;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class UserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

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
                Rule::unique('users', 'email')->ignore($userId)->withoutTrashed(),
                new UniqueCheck(
                    User::class,
                    __CLASS__,
                    $userId
                ),
            ],

            'password' => [
                'sometimes',
                'required',
                'confirmed',
                'min:8',
                config('project.auth.strong_password') ? new StrongPassword : '',
            ],

            'gender' => ['required', new Enum(UserGenderEnum::class)],
            'avatar' => ['sometimes', 'nullable', File::image()->max(20048)], // 20MB max

            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'numeric',
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
        if (! is_array($roles)) {
            $this->merge(['roles' => Arr::wrap($roles)]);
        }
    }
}
