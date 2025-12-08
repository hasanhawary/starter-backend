<?php

namespace App\Http\Requests\Global\Notification;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class NotificationRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'action' => 'required|in:open,read',
            'ids' => 'required_if:action,read|array',
            'ids.*' => ['required_with:ids', Rule::exists('notifications', 'id')],
        ];
    }
}
