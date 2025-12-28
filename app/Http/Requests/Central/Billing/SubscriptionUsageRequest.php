<?php

namespace App\Http\Requests\Central\Billing;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionUsageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'feature_key' => ['required', 'string'],
            'used_value' => ['required', 'integer', 'min:0'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }
}
