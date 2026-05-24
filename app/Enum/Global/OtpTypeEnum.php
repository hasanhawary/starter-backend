<?php

namespace App\Enum\Global;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum OtpTypeEnum: string
{
    use EnumMethods;

    case Login = 'login';
    case ResetPassword = 'reset_password';
    case VerifyEmail = 'verify_email';
}
