<?php

namespace App\Providers;

use App\Guards\SanctumGuard;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\Factory;
use Laravel\Sanctum\SanctumServiceProvider;

class ExtendedSanctumServiceProvider extends SanctumServiceProvider
{
    /**
     * Register the guard.
     *
     * @param  Factory  $auth
     * @param  array  $config
     */
    protected function createGuard($auth, $config): RequestGuard
    {
        return new RequestGuard(
            new SanctumGuard($auth, config('sanctum.expiration'), $config['provider']),
            request(),
            $auth->createUserProvider($config['provider'] ?? null)
        );
    }
}
