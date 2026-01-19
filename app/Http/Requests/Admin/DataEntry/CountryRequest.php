<?php

namespace App\Http\Requests\Admin\DataEntry;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Global\DataEntry\CountryResource;
use App\Models\Country;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;

class CountryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                new UniqueCheck(
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

            'code' => [
                'required',
                Rule::unique('countries', 'code')
                    ->withoutTrashed()
                    ->ignore($this->route('country'))
            ],
            'phone_code' => ['required'],
            'phone_length' => ['required', 'numeric'],
        ];
    }
}
