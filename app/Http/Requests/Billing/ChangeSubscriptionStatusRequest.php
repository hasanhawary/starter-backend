<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enum\Billing\SubscriptionStatusEnum;

class ChangeSubscriptionStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(SubscriptionStatusEnum::class)],
        ];
    }
}
