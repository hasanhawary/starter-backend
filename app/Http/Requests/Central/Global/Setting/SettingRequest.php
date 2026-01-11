<?php

namespace App\Http\Requests\Central\Global\Setting;

use App\Http\Requests\BaseFormRequest;

class SettingRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['required', 'string'],
        ];
    }
}
