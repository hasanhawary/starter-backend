<?php

namespace App\Providers;

use App\Models\Central\Admin;
use App\Models\Central\PersonalAccessToken;
use App\Models\Central\Role;
use App\Models\Tenant\User;
use App\Policies\Central\Admin\RolePolicy;
use App\Policies\Central\Admin\AdminPolicy;
use App\Policies\Tenant\User\UserPolicy;
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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        //Policies
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
   }
}
