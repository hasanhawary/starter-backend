<?php

namespace Modules\Export\App\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ExportFormatEnum: string
{
    use EnumMethods;

    case Excel = 'excel';
    case Pdf = 'pdf';
}
