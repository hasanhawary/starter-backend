<?php

namespace App\Enum\Subscription;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum PlanBillingCycleEnum: string
{
    use EnumMethods;

    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
