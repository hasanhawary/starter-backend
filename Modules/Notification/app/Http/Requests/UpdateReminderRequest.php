<?php

namespace Modules\Notification\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\ReminderSettingTypesEnum;
use Modules\Notification\app\Enum\ReminderSettingUnitsEnum;

class UpdateReminderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'reminder_setting' => 'required|array',
            'reminder_setting.*.channel' => ['required', new Enum(NotificationChannelEnum::class)],
            'reminder_setting.*.fields' => ['array', 'required_with:reminder_setting.*.channel'],
            'reminder_setting.*.fields.*.id' => 'nullable|integer|exists:reminders_setting,id',
            'reminder_setting.*.fields.*.reminder_based_on_column' => ['required_with:reminder_setting.*.fields', 'exists:notification_verifiable_dates,id'],
            'reminder_setting.*.fields.*.offset_type' => ['required', new Enum(ReminderSettingTypesEnum::class)],
            'reminder_setting.*.fields.*.offset_value' => 'required|integer',
            'reminder_setting.*.fields.*.offset_unit' => ['required', new Enum(ReminderSettingUnitsEnum::class)],
        ];
    }
}
