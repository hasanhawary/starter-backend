<?php

namespace Modules\Export\App\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Modules\Export\App\Enum\ExportFormatEnum;

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
            'format' => ['required', 'string', Rule::enum(ExportFormatEnum::class)],
            'columns' => 'sometimes|required|array',
        ];
    }
}
