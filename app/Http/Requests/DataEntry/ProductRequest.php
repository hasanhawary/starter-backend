<?php

namespace App\Http\Requests\DataEntry;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TranslatableRequired;
use App\Rules\TranslatableNullable;
use Illuminate\Validation\Rules\Enum;
use App\Enum\DataEntry\StatusEnum;


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
