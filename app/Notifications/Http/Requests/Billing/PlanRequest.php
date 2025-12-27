<?php

namespace App\Notifications\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enum\Billing\PlanBillingCycleEnum;

class PlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                Rule::unique('plans', 'code')->ignore($this->route('plan')),
            ],
            'name' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
'billing_cycle' => ['required', new Enum(PlanBillingCycleEnum::class)],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_storage_mb' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
