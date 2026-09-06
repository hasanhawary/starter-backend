<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;

class UpdateLicenseLimitsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'max_devices' => ['required', 'integer', 'min:1', 'max:500'],
            'max_branches' => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
