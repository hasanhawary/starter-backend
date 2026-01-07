<?php

namespace App\Http\Requests\Central\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class VerifyOTPRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->withoutTrashed(),
            ],
            'otp' => [
                'required', 'digits:4'
            ],
        ];
    }
}
