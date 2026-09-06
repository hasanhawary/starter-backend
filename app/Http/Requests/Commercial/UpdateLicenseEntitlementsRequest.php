<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;

class UpdateLicenseEntitlementsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'entitlements' => ['required', 'array'],
            'entitlements.*' => ['boolean'],
        ];
    }
}
