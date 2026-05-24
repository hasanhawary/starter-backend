<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseFormRequest;
use App\Models\Country;
use App\Rules\CheckSamePassword;
use App\Rules\StrongPassword;
use App\Rules\ValidLength;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

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

            'avatar' => [
                'nullable',
                File::image()->max(20480), // 20MB max
            ],

            'password' => [
                'sometimes',
                'nullable',
                'confirmed',
                'min:8',
                new CheckSamePassword,
                config('project.auth.strong_password') ? new StrongPassword : '',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $phone = $this->input('phone');

        // phone => ['phone' => '...', 'phone_code_id' => '...']
        if (is_array($phone)) {
            $this->merge([
                'phone' => $phone['phone'] ?? null,
                'phone_code_id' => $phone['phone_code_id'] ?? null,
            ]);
        }
    }
}
