<?php

namespace App\Http\Requests\Auth;

use App\Enum\Global\OtpTypeEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class VerifyOtpRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required',
            'type' => ['required', new Enum(OtpTypeEnum::class)],
        ];
    }
}
