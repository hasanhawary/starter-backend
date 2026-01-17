<?php

namespace App\Http\Requests\Central\Subscription;

use App\Http\Requests\BaseFormRequest;

class SubscriptionUsageRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'key' => ['required', 'string'],
            'used_value' => ['required', 'integer', 'min:0'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }
}
