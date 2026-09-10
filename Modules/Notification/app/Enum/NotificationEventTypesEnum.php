<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum NotificationEventTypesEnum: string
{
    use EnumMethods;

    case Reminder = 'reminder';
    case Notification = 'notification';
    case Calendar = 'calendar';
}
