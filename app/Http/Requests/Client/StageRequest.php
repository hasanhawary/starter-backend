<?php

namespace App\Http\Requests\Client;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TranslatableRequired;
use App\Rules\TranslatableNullable;


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
