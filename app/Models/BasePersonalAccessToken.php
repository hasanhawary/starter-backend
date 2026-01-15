<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Multitenancy\Models\Tenant;

abstract class BasePersonalAccessToken extends PersonalAccessToken
{
    public function __construct()
    {
        parent::__construct();
        $this->setConnection($this->detectConnection());
    }

    /**
     * Detect which guard to use.
     * Here you can customize logic based on request, route prefix, or header
     */
    protected function detectConnection(): string
    {
        if (Tenant::current()) {
            return config('multitenancy.tenant_database_connection_name');
        }

        return config('multitenancy.landlord_database_connection_name');
    }
}
