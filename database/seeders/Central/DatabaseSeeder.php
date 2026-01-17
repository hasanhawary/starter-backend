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
        $this->call([
            CountrySeeder::class,
            AdminTableSeeder::class,
            SettingTableSeeder::class,
            PlanTableSeeder::class,
            TenantTableSeeder::class,
        ]);
    }
}
