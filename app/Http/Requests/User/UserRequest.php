<?php

namespace App\Http\Requests\User;

use App\Enum\User\UserGenderEnum;
use App\Http\Requests\BaseFormRequest;
use App\Models\Country;
use App\Rules\ValidLength;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'country_id' => ['nullable', Rule::exists('countries', 'id')],
            'nationality_id' => ['nullable', Rule::exists('countries', 'id')],
            'phone_code_id' => ['nullable', Rule::exists('countries', 'id')],
            'phone' => [
                'nullable',
                'regex:/^[0-9]+$/',
                new ValidLength($this->input('country_id'), Country::class, 'phone_length'),
                Rule::unique('users', 'phone')->whereNull('deleted_at')->ignore($this->user?->id)
            ],
            'email' => [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($this->route('user'))->whereNull('deleted_at')
            ],
            'password' => ['sometimes', 'required', 'confirmed'],
            'gender' => ['required', new Enum(UserGenderEnum::class)],
            'avatar' => 'sometimes|nullable|' . vImage(),
            'roles' => ['required', Rule::exists('roles', 'id')],
            'is_active' => 'sometimes|boolean',
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'required_with:permissions|exists:permissions,name',
        ];
    }

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $phone = $this->input('phone');

        if (is_array($phone) && $phone_code_id = @$phone['phone_code_id']) {
            $this->merge([
                'phone' => @$phone['phone'],
                'phone_code_id' => $phone_code_id,
            ]);
        }
    }
}
