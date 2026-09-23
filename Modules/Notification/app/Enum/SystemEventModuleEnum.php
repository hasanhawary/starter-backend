<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SystemEventModuleEnum: string
{
    use EnumMethods;

    // Only modules that actually own system events (see system_events.json) are kept here.
    // This is the base template, so it carries the two domains it ships with: `country`
    // from the application itself, and `showcase` from the reference module that
    // demonstrates how a module owns notifiable events. Every other module arrives with
    // the project generated from it.
    case Country = 'country';
    case Showcase = 'showcase';

    public static function group(): array
    {
        return [
            [
                'group' => 'country',
                'display_group' => resolveTrans('countries'),
                'items' => self::getCustomList([self::Country]),
            ],
            [
                'group' => 'showcase',
                'display_group' => resolveTrans('showcases'),
                'items' => self::getCustomList([self::Showcase]),
            ],
        ];
    }
}
