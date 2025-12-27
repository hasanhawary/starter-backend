<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class PlanFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'feature_key' => ['required', 'string'],
            'value' => ['required', 'string'],
        ];
    }
}
