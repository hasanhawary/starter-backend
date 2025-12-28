<?php

namespace App\Http\Requests\Central\DataEntry;

use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;
use Illuminate\Foundation\Http\FormRequest;


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
