<?php

namespace App\Http\Requests\Global\Help;

use App\Http\Requests\BaseFormRequest;

class HelpEnumRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'enums' => ['sometimes', 'array'],
            'enums.*.name' => ['required_with:enums', 'string'],
            'enums.*.module' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
