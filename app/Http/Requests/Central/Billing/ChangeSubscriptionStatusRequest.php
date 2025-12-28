<?php

namespace App\Http\Requests\Central\Billing;

use App\Enum\Billing\SubscriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeSubscriptionStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(SubscriptionStatusEnum::class)],
        ];
    }
}
