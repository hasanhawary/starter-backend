<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ReportPageTypeEnum: string
{
    use EnumMethods;

    case User = 'user';
}
