<?php

namespace Modules\Export\App\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ExportStatusEnum: string
{
    use EnumMethods;

    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';

}
