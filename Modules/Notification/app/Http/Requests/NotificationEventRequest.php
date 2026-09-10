<?php

namespace Modules\Notification\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableNullable;
use Illuminate\Validation\Rules\Enum;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\NotificationEventTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingUnitsEnum;

class NotificationEventRequest extends BaseFormRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'is_reminder' => $this->boolean('is_reminder', false),
        ]);
    }

    public function rules(): array
    {
        return [
            'system_event_id' => 'required|numeric|exists:system_events,id',
            'name' => ['nullable', 'array', new TranslatableNullable('title', ['string'], 'title')],
            'is_reminder' => 'nullable|boolean',
            'type' => ['sometimes', new Enum(NotificationEventTypesEnum::class)],
            'variables' => 'nullable|array',
            'variables.*' => 'nullable|exists:variables,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required_if:type,notification|numeric',
            'relation_ids' => 'nullable|array',
            'relation_ids.*' => 'sometimes|numeric',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'sometimes|numeric',
            'templates' => 'required|array',
            'templates.*.channel' => ['required', 'string', new Enum(NotificationChannelEnum::class)],
            'templates.*.title' => ['nullable', 'array', new TranslatableNullable('title', ['string'], 'title')],
            'templates.*.body' => ['nullable', 'array', new TranslatableNullable('body', ['string'], 'body')],
            'templates.*.date_column' => ['nullable', 'required_if:templates.*.channel,reminder,calendar', 'exists:notification_verifiable_dates,id'],

            'reminder_setting' => 'nullable|array',
            'reminder_setting.*.id' => 'nullable|integer|exists:reminders_setting,id',
            'reminder_setting.*.channel' => ['nullable', new Enum(NotificationChannelEnum::class)],
            'reminder_setting.*.fields' => ['sometimes', 'array'],
            'reminder_setting.*.fields.*.id' => 'nullable|integer|exists:reminders_setting,id',
            'reminder_setting.*.fields.*.offset_type' => ['required_with:reminder_setting.*.fields', new Enum(ReminderSettingTypesEnum::class)],
            'reminder_setting.*.fields.*.offset_value' => 'required_with:reminder_setting.*.fields|integer',
            'reminder_setting.*.fields.*.offset_unit' => ['required_with:reminder_setting.*.fields', new Enum(ReminderSettingUnitsEnum::class)],
            'reminder_setting.*.fields.*.reminder_based_on_column' => ['required_with:reminder_setting.*.fields', 'exists:notification_verifiable_dates,id'],
        ];
    }
}
