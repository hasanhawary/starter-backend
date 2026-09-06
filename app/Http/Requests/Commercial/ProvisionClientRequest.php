<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ProvisionClientRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Step 1: Customer
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100|unique:organizations,slug',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:255',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:100',

            // Step 2: Plan
            'plan_id' => 'required|uuid|exists:plans,id',
            'max_devices' => 'required|integer|min:1|max:500',
            'max_branches' => 'required|integer|min:1|max:100',
            'expires_at' => 'required|date|after:today',
            'grace_period_days' => 'nullable|integer|min:1|max:90',
            'contract_notes' => 'nullable|string|max:1000',
            'entitlement_overrides' => 'nullable|array',
            'limit_overrides' => 'nullable|array',

            // Step 3: Deployment
            'deployment_mode' => 'required|in:local,cloud,hybrid',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            // 'subdomain'       => 'nullable|required_if:deployment_mode,cloud|string|max:63|unique:deployments,subdomain', // Phase 2

            // Step 4: Initial Admin
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'nullable|string|min:8',
        ];
    }
}
