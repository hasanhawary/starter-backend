<?php

namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Models\Tenant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure central context
        Tenant::withoutTenant(function () {
            $this->call([
                CountrySeeder::class,
                AdminTableSeeder::class,
                SettingTableSeeder::class,
                TenantTableSeeder::class,
            ]);
        });
    }
}
