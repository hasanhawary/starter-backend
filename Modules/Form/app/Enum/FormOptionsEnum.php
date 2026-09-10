<?php

namespace Modules\Form\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum FormOptionsEnum: string
{
    use EnumMethods;

    case Static = 'static';
    case HelpModel = 'help-model';
    case HelpConfig = 'help-config';
    case HelpEnum = 'help-enum';
    case InternalUrl = 'internal-url';
    case ExternalUrl = 'external-url';
}
