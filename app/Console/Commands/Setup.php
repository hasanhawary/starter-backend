<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use Nwidart\Modules\Facades\Module;
use PDO;
use Random\RandomException;
use Throwable;
use Database\Seeders\Tenant\DatabaseSeeder;

class Setup extends Command
{
    protected string $db;
    protected string $defaultConnection;

    protected $signature = 'app:install
        {--db-host=localhost : Database host}
        {--db-port=3306 : Database port}
        {--db-database= : Database name}
        {--db-username=root : Database username}
        {--db-password=root : Database password}
        {--no-seed : Do not run database seeders}';

    protected $description = 'Install and bootstrap the application';

    public function handle(): void
    {
        $this->defaultConnection = config('database.default');

        $this->warn('🚀  Application installation started...');

        $this->copyEnvExampleToEnv();
        $this->updateEnvVariablesFromOptions();

        Artisan::call('key:generate', ['--force' => true]);
        $this->info('✔ Application key generated.');

        $this->createDatabase();

        if ($this->isUsingLaravelModules()) {
            $this->setupModules();
        }

        Artisan::call('storage:link');
        $this->info('✔ Storage linked.');

        $this->info(' ✅ Application installation completed successfully.');
        $this->displaySampleUserCredentials();
    }

    private function copyEnvExampleToEnv(): void
    {
        if (!File::exists(base_path('.env')) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->info('✔ .env file created.');
        }
    }

    private function updateEnvVariablesFromOptions(): void
    {
        $this->db = $this->option('db-database')
            ?: Str::snake(config('app.name')) . '_' . random_int(999, 9999) . '_db';

        updateDotEnv([
            'DB_HOST' => $this->option('db-host'),
            'DB_PORT' => $this->option('db-port'),
            'DB_DATABASE' => $this->db,
            'DB_USERNAME' => $this->option('db-username'),
            'DB_PASSWORD' => $this->option('db-password'),
            'FILESYSTEM_DISK' => 'public',
        ]);

        $this->info('✔ Environment variables updated.');
    }

    private function createDatabase(): void
    {
        $connection = $this->defaultConnection;
        $config = config("database.connections.$connection");

        try {
            // Create database via PDO
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']}";

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$this->db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            $this->info("✔ Database [{$this->db}] ready.");

            // Rebind Laravel connection
            DB::purge($connection);
            config(["database.connections.$connection.database" => $this->db]);
            DB::reconnect($connection);
            DB::connection($connection)->getPdo();

            // ✅ Run central + tenant migrations in PHP
            if (!$this->option('no-seed')) {
                $this->runCentralMigrationsAndSeeders();
                $this->runTenantMigrationsAndSeeders();
            }

        } catch (Throwable $e) {
            $this->error('❌ Database setup failed: ' . $e->getMessage());
            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("DROP DATABASE IF EXISTS `{$this->db}`");
            throw $e;
        }
    }

    private function runCentralMigrationsAndSeeders(): void
    {
        $this->warn('Running central migrations...');
        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
        $this->info('✔ Central migrations completed.');

        $this->warn('Seeding central database...');
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\Central\DatabaseSeeder::class,
            '--force' => true,
        ]);
        $this->info('✔ Central seeders executed.');
    }

    private function runTenantMigrationsAndSeeders(): void
    {
        $this->warn('Running tenant migrations...');
        Artisan::call("tenants:artisan 'migrate --path=database/migrations/tenant --database=tenant --force'");
        $this->info('✔ Tenant migrations completed.');

        $this->warn('Seeding tenant databases...');
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force',
        ]);
        $this->info('✔ Tenant seeders executed.');
    }

    /**
     * @throws FileNotFoundException
     * @throws JsonException
     */
    private function isUsingLaravelModules(): bool
    {
        $composer = json_decode(
            File::get(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return isset($composer['require']['nwidart/laravel-modules']);
    }

    private function setupModules(): void
    {
        $this->warn('Setting up modules...');

        foreach (Module::all() as $module) {
            $name = $module->getName();

            Artisan::call("module:enable {$name}");
            Artisan::call("module:migrate {$name}", ['--force' => true]);

            if (!$this->option('no-seed')) {
                Artisan::call("module:seed {$name}", ['--force' => true]);
            }

            $this->info("✔ Module {$name} installed.");
        }
    }

    private function displaySampleUserCredentials(): void
    {
        $domain = Str::snake(config('brands.default_brand', config('app.name')));

        $this->table(
            ['Name', 'Email', 'Password'],
            [['root', "root@{$domain}.com", '123456']]
        );
    }
}
