<?php

namespace App\Http\Requests\DataEntry;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Central\DataEntry\CountryResource;
use App\Models\Country;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueWithTrashed;
use Illuminate\Validation\Rule;

class CountryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                new UniqueWithTrashed(
                    Country::class,
                    CountryResource::class,
                    $this->route('country')?->id
                ),
                new TranslatableRequired('countries', ['string', 'max:191'], 'country')
            ],

            'nationality' => [
                'required',
                'array',
                new TranslatableRequired('countries', ['string', 'max:191'], 'country')
            ],

            'code' => ['required', Rule::unique('countries', 'code')->ignore($this->route('country'))],
            'phone_code' => ['required'],
            'phone_length' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('attributes.name')]),
            'name.array' => __('validation.array', ['attribute' => __('attributes.name')]),
            'name.ar.required' => __('validation.required', ['attribute' => __('attributes.name_ar')]),
            'name.ar.string' => __('validation.string', ['attribute' => __('attributes.name_ar')]),
            'name.ar.max' => __('validation.max.string', ['attribute' => __('attributes.name_ar'), 'max' => 191]),
            'name.en.required' => __('validation.required', ['attribute' => __('attributes.name_en')]),
            'name.en.string' => __('validation.string', ['attribute' => __('attributes.name_en')]),
            'name.en.max' => __('validation.max.string', ['attribute' => __('attributes.name_en'), 'max' => 191]),

            'nationality.required' => __('validation.required', ['attribute' => __('attributes.nationality')]),
            'nationality.array' => __('validation.array', ['attribute' => __('attributes.nationality')]),
            'nationality.ar.required' => __('validation.required', ['attribute' => __('attributes.nationality_ar')]),
            'nationality.ar.string' => __('validation.string', ['attribute' => __('attributes.nationality_ar')]),
            'nationality.ar.max' => __('validation.max.string', ['attribute' => __('attributes.nationality_ar'), 'max' => 191]),
            'nationality.en.required' => __('validation.required', ['attribute' => __('attributes.nationality_en')]),
            'nationality.en.string' => __('validation.string', ['attribute' => __('attributes.nationality_en')]),
            'nationality.en.max' => __('validation.max.string', ['attribute' => __('attributes.nationality_en'), 'max' => 191]),

            'code.required' => __('validation.required', ['attribute' => __('attributes.code')]),
            'phone_code.required' => __('validation.required', ['attribute' => __('attributes.phone_code')]),
            'phone_length.required' => __('validation.required', ['attribute' => __('attributes.phone_length')]),
            'phone_length.numeric' => __('validation.numeric', ['attribute' => __('attributes.phone_length')]),
        ];
    }
}
