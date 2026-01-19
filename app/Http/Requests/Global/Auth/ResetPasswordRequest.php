<?php

namespace App\Http\Requests\Global\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Str;

class ResetPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'otp' => ['required', 'digits:4'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
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
                    'password_confirmation' => $decoded,
                ]);
            }
        }
    }
}
