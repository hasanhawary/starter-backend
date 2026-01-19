<?php

namespace App\Http\Requests\Global\Other;

use App\Http\Requests\BaseFormRequest;

class PageRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'page' => 'nullable|numeric|min:1',
            'pageSize' => 'nullable|min:1',
        ];
    }
}
