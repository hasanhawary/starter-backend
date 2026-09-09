<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ReminderSettingTypesEnum: string
{
    use EnumMethods;

    case Before = 'before';
    case After = 'after';
}
