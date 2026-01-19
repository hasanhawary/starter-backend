<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\PersonalAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Policies\Admin\User\RolePolicy;
use App\Policies\Admin\User\AdminPolicy;
use App\Policies\Admin\User\UserPolicy;
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
