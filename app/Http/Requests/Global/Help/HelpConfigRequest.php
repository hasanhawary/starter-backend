<?php

namespace App\Http\Requests\Global\Help;

use Illuminate\Foundation\Http\FormRequest;

class HelpConfigRequest extends FormRequest
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

    public function attributes(): array
    {
        return [
            'configs.*.name' => __('attributes.configs_name'),
            'configs.*.keys' => __('attributes.configs_keys'),
            'configs.*.keys.*' => __('attributes.configs_keys_item'),
        ];
    }
}
