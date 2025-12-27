<?php

namespace App\Notifications\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enum\Billing\SubscriptionStatusEnum;

class SubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string'],
            'plan_id' => ['required', 'exists:plans,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
'status' => ['required', new Enum(SubscriptionStatusEnum::class)],
        ];
    }
}
