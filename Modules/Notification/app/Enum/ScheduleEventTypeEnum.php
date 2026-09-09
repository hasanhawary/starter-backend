<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ScheduleEventTypeEnum: string
{
    use EnumMethods;

    case Reminder = 'reminder';
    case Calendar = 'calendar';
}
