<?php

namespace App\Providers;

use App\Models\Central\Admin;
use App\Models\Central\Role;
use App\Models\Tenant\User;
use App\Observers\User\UserObserver;
use App\Policies\Central\Admin\RolePolicy;
use App\Policies\Central\Admin\AdminPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Central Policies

        //Policies
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        //Observers
        User::observe(UserObserver::class);
    }
}
