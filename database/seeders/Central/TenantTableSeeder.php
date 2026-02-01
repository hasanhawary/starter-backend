<?php

namespace Database\Seeders\Central;

use App\Models\Central\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantTableSeeder extends Seeder
{
    public function run()
    {
        $tenants = [
            [
                'name' => 'Tenant One',
                'domain' => 'tenant1.test',
                'database' => 'tenant_1',
                'created_by' => 1,
            ],
            [
                'name' => 'Tenant Two',
                'domain' => 'tenant2.test',
                'database' => 'tenant_2',
                'created_by' => 1,
            ],
        ];

        foreach ($tenants as $tenant) {
            $tenant =  Tenant::firstOrCreate($tenant);
            DB::statement("DROP DATABASE IF EXISTS `{$tenant->database}`");
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$tenant->database}`");
        }
    }
}
