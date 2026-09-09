<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * Enum for reminder_based_on_column types
 * These are the types of date columns used in reminders settings.
 */
enum ReminderSettingColumnTypeEnum: string
{
    use EnumMethods;

    case Date = 'date';
    case Datetime = 'datetime';
}

