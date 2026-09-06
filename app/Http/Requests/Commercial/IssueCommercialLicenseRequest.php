<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class IssueCommercialLicenseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['nullable', 'uuid', Rule::exists('plans', 'id')],
            'plan_code' => ['nullable', 'string', Rule::exists('plans', 'code')],
            'max_devices' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_branches' => ['nullable', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'grace_period_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'entitlement_overrides' => ['nullable', 'array'],
            'limit_overrides' => ['nullable', 'array'],
            'limit_overrides.max_devices' => ['nullable', 'integer', 'min:1', 'max:500'],
            'limit_overrides.max_branches' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
