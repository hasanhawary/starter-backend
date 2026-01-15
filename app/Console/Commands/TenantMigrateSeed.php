<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Database\Seeders\Tenant\DatabaseSeeder;

class TenantMigrateSeed extends Command
{
    protected $signature = 'tenants:migrate-seed';
    protected $description = 'Run migrations and seeders for all tenants';

    public function handle()
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Running migrations for tenant `{$tenant->name}` (id: {$tenant->id})...");

            $tenant->makeCurrent();

            // Migrate tenant DB
            $this->call('migrate:fresh', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
                '--database' => 'tenant',
            ]);

            $this->info("Seeding tenant `{$tenant->name}`...");
            $this->call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);

            $tenant->forgetCurrent();

            $this->info("✅ Done for tenant `{$tenant->name}`.\n");
        }

        $this->info("All tenants migrated and seeded successfully!");
    }
}
