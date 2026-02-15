<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SettingTypeEnum: string
{
    use EnumMethods;

    case Text = 'text';
    case TextArea = 'textarea';

    case ImageUploader = 'imageUploader';
    case File = 'file';
    case CheckBox = 'checkBox';
    case Radio = 'radio';
    case SwitchBox = 'switchbox';

}
