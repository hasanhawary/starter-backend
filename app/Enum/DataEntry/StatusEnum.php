<?php

namespace App\Enum\DataEntry;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum StatusEnum: string
{
    use EnumMethods;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
