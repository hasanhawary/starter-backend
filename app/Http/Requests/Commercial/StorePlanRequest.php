<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-z0-9_-]+$/i', Rule::unique('plans', 'code')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:160'],
            'name.en' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:500'],
            'description.en' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
            'default_entitlements' => ['nullable', 'array'],
            'default_limits' => ['required', 'array'],
            'default_limits.max_devices' => ['required', 'integer', 'min:1', 'max:500'],
            'default_limits.max_branches' => ['required', 'integer', 'min:1', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
