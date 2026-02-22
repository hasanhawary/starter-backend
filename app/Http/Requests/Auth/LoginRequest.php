<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Str;

class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'otp' => [shouldVerifyOtp() ? 'required' : 'nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('password') && config('project.auth.encryption.incoming.password')) {
            $decoded = base64_decode(
                Str::replaceEnd(
                    'HM',
                    '',
                    Str::replaceFirst('KZ', '', $this->password)
                ),
                true
            );

            if ($decoded !== false) {
                $this->merge([
                    'password' => $decoded,
                ]);
            }
        }
    }
}
