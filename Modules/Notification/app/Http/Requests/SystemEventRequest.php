<?php

namespace Modules\Notification\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class SystemEventRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'variables' => 'nullable|array',
            'variables.*' => 'numeric|exists:variables,id',
        ];
    }
}
