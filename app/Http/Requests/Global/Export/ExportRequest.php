<?php

namespace App\Http\Requests\Global\Export;

use App\Http\Requests\BaseFormRequest;

class ExportRequest extends BaseFormRequest
{
    /**
     * @return array<string>
     */
    public function rules(): array
    {
        return [
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'page' => 'required|string',
            'columns' => 'sometimes|required|array',
        ];
    }
}
