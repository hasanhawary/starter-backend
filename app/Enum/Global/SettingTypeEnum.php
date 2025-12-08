<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SettingTypeEnum: string
{
    use EnumMethods;

    case Text = 'text';
    case ImageUploader = 'imageUploader';
    case File = 'file';
}
