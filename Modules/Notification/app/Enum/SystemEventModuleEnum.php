<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SystemEventModuleEnum: string
{
    use EnumMethods;

    // Only modules that actually own system events (see system_events.json) are kept here.
    // This is the base template, so it carries the one domain it can actually resolve a
    // model for: every other module arrives with the project generated from it.
    case Country = 'country';

    public static function group(): array
    {
        return [
            [
                'group' => 'country',
                'display_group' => resolveTrans('countries'),
                'items' => self::getCustomList([self::Country]),
            ],
        ];
    }
}
