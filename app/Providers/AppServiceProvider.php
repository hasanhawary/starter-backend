<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Policies\User\RolePolicy;
use App\Policies\User\UserPolicy;
use App\Services\Global\DiscoveryConfigResolver;
use HasanHawary\LookupManager\ConfigLookupManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Resolve `discovery` filter labels at request time so `help-configs`
        // returns them translated in the current locale (rebound in boot() to
        // win over the LookupManager package's own binding).
        $this->app->singleton(ConfigLookupManager::class, function ($app) {
            return new ConfigLookupManager(
                configResolver: $app->make(DiscoveryConfigResolver::class),
            );
        });

        // Policies
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
