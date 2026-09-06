<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class PreviewPlanChangeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required_without:plan_code', 'nullable', 'uuid', Rule::exists('plans', 'id')],
            'plan_code' => ['required_without:plan_id', 'nullable', 'string', Rule::exists('plans', 'code')],
            'entitlement_overrides' => ['nullable', 'array'],
            'limit_overrides' => ['nullable', 'array'],
            'limit_overrides.max_devices' => ['nullable', 'integer', 'min:1', 'max:500'],
            'limit_overrides.max_branches' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
