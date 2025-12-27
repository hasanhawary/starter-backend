<?php

namespace App\Enum\Billing;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum PlanBillingCycleEnum: string
{
    use EnumMethods;

    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
