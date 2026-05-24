<?php

namespace App\Http\Requests\Global\Help;

use App\Http\Requests\BaseFormRequest;

class HelpEnumRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'enums' => ['sometimes', 'required', 'array'],
            'enums.*.name' => ['sometimes', 'required', 'string'],
            'enums.*.module' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
