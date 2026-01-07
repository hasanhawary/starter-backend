<?php

namespace App\Http\Requests\Central\Auth;

use App\Http\Requests\BaseFormRequest;

class ForgetPasswordRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'email' => ['required', 'email']
        ];
    }
}
