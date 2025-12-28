<?php

namespace App\Http\Requests\Central\Client;

use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'array', new TranslatableRequired('name', ['string'], 'name')],
            'description' => ['nullable', 'array', new TranslatableNullable('description', ['string'], 'description')],
            'slug' => ['nullable', 'string', Rule::unique('stages', 'slug')->ignore($this->route('stage'))],
            'order' => ['required', 'integer'],
            'is_final' => ['nullable', 'boolean'],
        ];
    }
}
