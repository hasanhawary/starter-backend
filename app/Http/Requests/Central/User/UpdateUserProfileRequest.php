<?php

namespace App\Http\Requests\Central\User;

use App\Http\Requests\BaseFormRequest;
use App\Models\Country;
use App\Rules\CheckSamePassword;
use App\Rules\ValidLength;
use Illuminate\Validation\Rule;

class   UpdateUserProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'nationality_id' => ['required', Rule::exists('countries', 'id')],
            'phone_code_id' => ['nullable', Rule::exists('countries', 'id')],
            'phone' => [
                'nullable',
                'regex:/^[0-9]+$/',
                new ValidLength($this->input('country_id'), Country::class, 'phone_length'),
                Rule::unique('users', 'phone')
                    ->whereNull('deleted_at')
                    ->ignore(auth()->id()),
            ],
            'avatar' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp',
            'password' => ['sometimes', 'nullable', 'min:8', 'confirmed', new CheckSamePassword()],
        ];
    }
}
