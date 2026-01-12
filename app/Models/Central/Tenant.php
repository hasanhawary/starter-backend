<?php

namespace App\Models\Central;

class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    public bool $inPermission = true;
}
