<?php

namespace App\Http\Requests\Central\Subscription;

use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableNullable;

class PlanFeatureRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                new TranslatableNullable('plans', ['string', 'max:191'], 'planFeature')
            ],
            'key' => ['required', 'string'],
            'value' => ['required', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
        ];
    }
}
