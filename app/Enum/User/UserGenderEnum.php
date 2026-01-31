<?php

namespace App\Enum\User;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum UserGenderEnum: string
{
    use EnumMethods;

    case Male = 'male';
    case Female = 'female';
}
