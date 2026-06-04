<?php

namespace Tests;

use App\Models\Country;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function createCountry(array $attributes = []): Country
    {
        return Country::factory()->create($attributes);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function actingAsUserWithPermissions(array $permissions = [], ?User $user = null): User
    {
        $user ??= $this->createUser();

        $this->givePermissions($user, $permissions);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function givePermissions(User $user, array $permissions): void
    {
        $permissionModels = [];

        foreach ($permissions as $permission) {
            $permissionModels[] = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => config('roles.default_guard'),
            ], [
                'display_name' => [
                    'en' => $permission,
                    'ar' => $permission,
                ],
                'group' => 'testing',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions !== []) {
            $user->givePermissionTo($permissionModels);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function assertSuccessEnvelope(TestResponse $response): void
    {
        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('code', 200);
    }
}
