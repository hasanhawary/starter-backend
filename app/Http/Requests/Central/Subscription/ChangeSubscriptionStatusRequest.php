<?php

namespace App\Http\Requests\Central\Subscription;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeSubscriptionStatusRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(SubscriptionStatusEnum::class)],
        ];
    }
}
