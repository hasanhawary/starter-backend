<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;

class RenewLicenseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'expires_at' => ['required', 'date', 'after:now'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
