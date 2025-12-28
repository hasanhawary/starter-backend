<?php

namespace App\Http\Requests\Central\Auth;

use App\Http\Requests\BaseFormRequest;

class VerifyOTPRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email,deleted_at,NULL',
            'otp' => 'required|string|digits:4'
        ];
    }
}
