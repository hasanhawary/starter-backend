<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableRequired;
use Illuminate\Validation\Rule;

class PermissionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $permission = $this->route('permission');

        return [
            'name' => [
                'required',
                Rule::unique('permissions', 'name')->ignore($permission),
            ],

            'display_name' => [
                'required',
                'array',
                new TranslatableRequired('permissions', ['string', 'max:191'], 'country'),
            ],

            'group' => 'required',
        ];
    }
}
