<?php

namespace App\Http\Requests\Global\Notification;

use App\Http\Requests\BaseFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class NotificationRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'action' => 'required|in:open,read',
            'ids' => 'required_if:action,read|array',
            'ids.*' => [
                'required_with:ids',
                Rule::exists('notifications', 'id')->where(fn ($query) => $query
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $this->user()?->id)),
            ],
        ];
    }
}
