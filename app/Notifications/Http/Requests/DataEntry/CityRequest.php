<?php

namespace App\Http\Requests\DataEntry;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TranslatableRequired;
use App\Rules\TranslatableNullable;


class CityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'array', new TranslatableRequired('name', ['string'], 'name')],
            'description' => ['nullable', 'array', new TranslatableNullable('description', ['string'], 'description')],
            'country_id' => ['nullable', 'exists:countries,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
