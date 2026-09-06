<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateCommercialOrganizationRequest extends BaseFormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge([
                'name' => [
                    'ar' => $this->input('name'),
                    'en' => $this->input('name_en') ?: $this->input('name'),
                ],
            ]);
        }

        if ($this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('slug')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'min:2', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'min:2', 'max:100', Rule::unique('organizations', 'slug')],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_admin_name' => ['nullable', 'string', 'max:255'],
            'initial_admin_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'initial_admin_password' => ['nullable', 'string', 'min:8', 'max:100'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
