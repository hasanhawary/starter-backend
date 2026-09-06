<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class ActivateLicenseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'min:12', 'max:128'],
            'branch_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('branches', 'id')],
            'device_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('devices', 'id')],
            'installation_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'device_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'platform' => ['sometimes', 'nullable', 'string', Rule::in(['windows', 'macos', 'linux', 'pos-web', 'other'])],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
