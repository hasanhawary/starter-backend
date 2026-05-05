<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum NotificationGroupEnum: int
{
    use EnumMethods;

    case Global = 1;

}
