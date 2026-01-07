<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Models\Tenant;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            $this->call([
                UserTableSeeder::class,
                SettingTableSeeder::class,
            ]);

            $tenant->forgetCurrent();
        }
    }
}
