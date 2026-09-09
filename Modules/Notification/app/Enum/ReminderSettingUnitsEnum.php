<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ReminderSettingUnitsEnum: string
{
    use EnumMethods;

    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
}
