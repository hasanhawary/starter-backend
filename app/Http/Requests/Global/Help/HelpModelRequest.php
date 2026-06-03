<?php

namespace App\Http\Requests\Global\Help;

use App\Http\Requests\BaseFormRequest;

class HelpModelRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'tables' => ['sometimes', 'array'],
            'tables.*.name' => ['required_with:tables', 'string'],
            'tables.*.module' => ['sometimes', 'nullable', 'string'],
            'tables.*.extra' => ['sometimes', 'nullable', 'array'],
            'tables.*.extra.*' => ['string'],
            'tables.*.scopes' => ['sometimes', 'nullable', 'array'],
            'tables.*.scopes.*' => ['string'],
            'tables.*.values' => ['sometimes', 'nullable', 'array'],
            'tables.*.search' => ['sometimes', 'nullable'],
            'tables.*.search.term' => ['required_with:tables.*.search', 'string'],
            'tables.*.search.fields' => ['sometimes', 'array'],
            'tables.*.search.fields.*' => ['string'],
            'tables.*.with' => ['sometimes', 'array'],
            'tables.*.with.*' => ['string'],
            'tables.*.paginate' => ['sometimes', 'boolean'],
            'tables.*.per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('project.pagination.max', 1000)],
            'tables.*.page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
