<?php

namespace App\Enum\Billing;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SubscriptionStatusEnum: string
{
    use EnumMethods;

    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
