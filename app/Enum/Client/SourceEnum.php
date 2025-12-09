<?php

namespace App\Enum\Client;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SourceEnum: string
{
    use EnumMethods;

    case Call = 'call';
    case Website = 'website';
    case Facebook = 'facebook';
    case Referral = 'referral';
    case Walkin = 'walkin';
    case Portal = 'portal';
}
