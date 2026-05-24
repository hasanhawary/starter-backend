<?php

namespace App\Http\Requests\Global\Other;

use App\Http\Requests\BaseFormRequest;

class OrderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'order' => ['required', 'int'],
        ];
    }
}
