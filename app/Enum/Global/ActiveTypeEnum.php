<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ActiveTypeEnum: int
{
    use EnumMethods;

    case Active = 1;
    case InActive = 0;
}
