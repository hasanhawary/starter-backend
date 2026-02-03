<?php

namespace App\Http\Requests\Auth;

use App\Enum\Global\OtpTypeEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class SendOtpRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'type' => ['required', new Enum(OtpTypeEnum::class)],
        ];
    }
}
