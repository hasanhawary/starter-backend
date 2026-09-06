<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:160'],
            'name.en' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:500'],
            'description.en' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
            'default_entitlements' => ['sometimes', 'array'],
            'default_limits' => ['sometimes', 'array'],
            'default_limits.max_devices' => ['required_with:default_limits', 'integer', 'min:1', 'max:500'],
            'default_limits.max_branches' => ['required_with:default_limits', 'integer', 'min:1', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
