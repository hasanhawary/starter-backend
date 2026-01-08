<?php

namespace App\Http\Requests\Central\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $tenantId = $this->route('tenant') ? $this->route('tenant')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('tenants', 'email')->ignore($tenantId)],
            'password' => [$tenantId ? 'nullable' : 'required', 'string', 'min:8'],
            'phone_code_id' => ['nullable', 'integer', 'exists:countries,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        // normalize phone
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\s+/', '', $this->input('phone'))]);
        }
    }
}

