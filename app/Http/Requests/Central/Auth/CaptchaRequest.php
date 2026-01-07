<?php

namespace App\Http\Requests\Central\Auth;

use App\Http\Requests\BaseFormRequest;

class CaptchaRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'captcha' => 'required',
            'token' => 'required'
        ];
    }
}
