<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class RecoverLicenseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'min:12', 'max:128'],
            'recovery_token' => ['required', 'string', 'min:8', 'max:64'],
            'installation_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'device_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'platform' => ['sometimes', 'nullable', 'string', Rule::in(['windows', 'macos', 'linux', 'pos-web', 'other'])],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
