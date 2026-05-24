<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ReportChartTypeEnum: string
{
    use EnumMethods;

    case HighChart = 'high_chart';

    public static function default(): string
    {
        return self::HighChart->value;
    }
}
