<?php

namespace App\Http\Requests\Global\Help;

use App\Http\Requests\BaseFormRequest;

class HelpModelRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'tables' => ['sometimes','required', 'array'],
            'tables.*.name' => ['required', 'string'],
            'tables.*.extra' => ['sometimes', 'nullable'],
            'tables.*.scopes.*' => ['sometimes', 'nullable'],
        ];
    }
}
