<?php

namespace App\Enum\Tenant;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum TenantStatusEnum: string
{
    use EnumMethods;

    case Pending = 'pending';              // created, not provisioned yet
    case Provisioning = 'provisioning';    // database / tenant setup in progress
    case Ready = 'ready';                 // tenant fully active
    case Failed = 'failed';              // provisioning failed

    public static function default(): string
    {
        return self::Pending->value;
    }
}
