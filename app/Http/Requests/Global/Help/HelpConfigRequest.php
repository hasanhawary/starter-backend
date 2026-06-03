<?php

namespace App\Http\Requests\Global\Help;

use App\Http\Requests\BaseFormRequest;

class HelpConfigRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'configs' => ['required', 'array'],
            'configs.*.name' => ['required', 'string'],
            'configs.*.keys' => ['sometimes', 'array'],
            'configs.*.keys.*' => ['string'],
        ];
    }
}
