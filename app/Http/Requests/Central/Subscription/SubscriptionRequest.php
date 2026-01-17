<?php

namespace App\Http\Requests\Central\Subscription;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class SubscriptionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
            'plan_price_id' => ['nullable', 'exists:plan_prices,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
