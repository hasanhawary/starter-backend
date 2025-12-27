<?php

namespace App\Http\Requests\Auth;

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

    public function messages(): array
    {
        return [
            'captcha.required' => __('validation.required', ['attribute' => __('attributes.captcha')]),
            'token.required' => __('validation.required', ['attribute' => __('attributes.token')]),
        ];
    }
}
