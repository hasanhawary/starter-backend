<?php

namespace App\Http\Requests\Central\DataEntry;

use App\Enum\DataEntry\StatusEnum;
use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;


class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'array', new TranslatableRequired('name', ['string'], 'name')],
            'description' => ['nullable', 'array', new TranslatableNullable('description', ['string'], 'description')],
            'phone' => ['nullable', 'string', Rule::unique('products', 'phone')->ignore($this->route('product'))],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'country_id' => ['nullable', 'exists:countries,id'],
        ];
    }
}
