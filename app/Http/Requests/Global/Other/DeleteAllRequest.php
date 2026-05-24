<?php

namespace App\Http\Requests\Global\Other;

use App\Http\Requests\BaseFormRequest;

class DeleteAllRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required'],
        ];
    }
}
