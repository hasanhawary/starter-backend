<?php

namespace App\Http\Requests\Global\Setting;

use App\Http\Requests\BaseFormRequest;

class TestCredentialsRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'body' => 'required|string',
        ];
    }
}
