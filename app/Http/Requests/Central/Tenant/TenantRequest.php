<?php

namespace App\Http\Requests\Central\Tenant;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Central\Tenant\TenantResource;
use App\Models\Central\Tenant;
use App\Rules\UniqueCheck;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TenantRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],

            'domain' => [
                'required',
                'string',
                'max:255',
//                new UniqueCheck(
//                    Tenant::class,
//                    TenantResource::class,
//                    $tenantId,
//                ),
            ],

            'database' => [
                'required',
                'string',
                'max:255',
//                new UniqueCheck(
//                    Tenant::class,
//                    TenantResource::class,
//                    $tenantId,
//                ),
            ],

            'settings' => ['nullable', 'array'],

            // Subscription fields
            'plan_price_id' => ['nullable', 'exists:plan_prices,id'],
            'subscription_starts_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Normalize inputs before validation
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Ensure settings is an array
        $settings = $this->input('settings', []);
        if (!is_array($settings)) {
            $this->merge(['settings' => Arr::wrap($settings)]);
        }
    }
}
