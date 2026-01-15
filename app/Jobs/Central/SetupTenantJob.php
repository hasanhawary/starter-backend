<?php

namespace App\Jobs\Central;

use App\Enum\Tenant\TenantStatusEnum;
use App\Models\Central\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;
use Database\Seeders\Tenant\DatabaseSeeder;

class SetupTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        // Set status to PROVISIONING
        $this->tenant->update(['status' => TenantStatusEnum::Provisioning->value]);

        // Create database
        DB::statement(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $this->tenant->database
        ));

        // Make tenant current
        $this->tenant->makeCurrent();

        try {
            // Run migrations
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // Run seeders
            Artisan::call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);

            // Mark tenant READY
            $this->tenant->update(['status' => TenantStatusEnum::Ready->value]);
        } catch (Throwable $exception) {
            $this->failed($exception);
        } finally {
            $this->tenant->forgetCurrent();
        }
    }

    public function failed(Throwable $exception): void
    {
        \Log::critical('Tenant setup permanently failed', [
            'tenant_id' => $this->tenant->id,
            'error'     => $exception->getMessage(),
        ]);

        // Update status to FAILED
        $this->tenant->update(['status' => TenantStatusEnum::Failed->value]);
    }
}
