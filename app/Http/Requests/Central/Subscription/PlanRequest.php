<?php

namespace App\Http\Requests\Central\Subscription;

use App\Enum\Subscription\PlanBillingCycleEnum;
use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PlanRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                Rule::unique('plans', 'code')->ignore($this->route('plan')),
            ],

            'name' => [
                'required',
                'array',
                new TranslatableNullable('plans', ['string', 'max:191'], 'plan')
            ],

            // Plan prices - nested array
            'prices' => ['sometimes', 'array'],
            'prices.*.cycle' => ['required_with:prices', 'string', 'in:monthly,yearly'],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
            'prices.*.currency' => ['required_with:prices', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'prices.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // If prices not provided, create default prices from main price
        if (!$this->has('prices') && $this->filled('price')) {
            $this->merge([
                'prices' => [
                    [
                        'cycle' => PlanBillingCycleEnum::Monthly->value,
                        'price' => $this->input('price'),
                        'currency' => $this->input('currency'),
                    ],
                ],
            ]);
        }
    }
}
