<?php

namespace App\Console\Commands;

use Database\Seeders\Central\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use Nwidart\Modules\Facades\Module;
use PDO;
use RuntimeException;
use Throwable;

class Setup extends Command
{
    protected string $db;
    protected string $defaultConnection;
    protected string $tenantConnection;


    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:install
        {--db-host=localhost : Database host}
        {--db-port=3306 : Database port}
        {--db-database= : Database name}
        {--db-username=root : Database username}
        {--db-password=root : Database password}
        {--no-seed : Do not run database seeders}';

    /**
     * The console command description.
     */
    protected $description = 'Install and bootstrap the application';

    /**
     * Execute the console command.
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->defaultConnection = config('multitenancy.landlord_database_connection_name');
        $this->tenantConnection = config('multitenancy.tenant_database_connection_name');

        $this->warn('🚀  Application installation started...');

        $this->copyEnvExampleToEnv();

        $this->db = $this->option('db-database')
            ?: Str::snake(config('app.name')) . '_' . random_int(999, 9999) . '_db';

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

    /**
     */
    private function updateEnvVariablesFromOptions(): void
    {
        updateDotEnv([
            'DB_HOST' => $this->option('db-host'),
            'DB_PORT' => $this->option('db-port'),
            'DB_DATABASE' => $this->db,
            'DB_USERNAME' => $this->option('db-username'),
            'DB_PASSWORD' => $this->option('db-password'),
            'FILESYSTEM_DISK' => 'public',
        ]);

        // Update runtime config from env
        config([
            "database.connections.{$this->defaultConnection}.host" => $this->option('db-host'),
            "database.connections.{$this->defaultConnection}.port" => $this->option('db-host'),
            "database.connections.{$this->defaultConnection}.database" => $this->db,
            "database.connections.{$this->defaultConnection}.username" => $this->option('db-username'),
            "database.connections.{$this->defaultConnection}.password" => $this->option('db-password'),
        ]);


        config([
            "database.connections.{$this->tenantConnection}.host" => $this->option('db-host'),
            "database.connections.{$this->tenantConnection}.port" => $this->option('db-host'),
            "database.connections.{$this->tenantConnection}.username" => $this->option('db-username'),
            "database.connections.{$this->tenantConnection}.password" => $this->option('db-password'),
        ]);

        Artisan::call('config:clear');

        $this->info('✔ Environment variables updated.');
    }

    /**
     * @throws Throwable
     */
    private function createDatabase(): void
    {
        $connection = $this->defaultConnection;
        $config = config("database.connections.$connection");

        try {
            // Create database via PDO (MySQL only)
            if ($config['driver'] !== 'mysql') {
                throw new RuntimeException('Database creation is supported only for MySQL');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s',
                $config['host'],
                $config['port']
            );

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );

            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$this->db}`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            $this->info("✔ Database [{$this->db}] ready.");

            // Rebind Laravel connection
            DB::purge($connection);
            DB::reconnect($connection);
            DB::connection($connection)->getPdo();

            //Migrate && Seed
            $this->runCentralMigrationsAndSeeders();
            $this->runTenantMigrationsAndSeeders();

        } catch (Throwable $e) {
            $this->error('❌ Database setup failed: ' . $e->getMessage());

            // Only attempt DROP DATABASE for MySQL
            if ($config['driver'] === 'mysql') {

                $dsn = sprintf(
                    'mysql:host=%s;port=%s',
                    $config['host'],
                    $config['port']
                );

                $pdo = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]
                );

                $pdo->exec("DROP DATABASE IF EXISTS `{$this->db}`");
            }

            throw $e;
        }
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
            Artisan::call("module:migrate:fresh {$name}", ['--force' => true]);

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

    private function runTenantMigrationsAndSeeders(): void
    {
        $this->warn('Running tenant migrations...');
        Artisan::call("tenants:artisan 'migrate --path=database/migrations/tenant --database=tenant --force'");
        $this->info('✔ Tenant migrations completed.');

        if (!$this->option('no-seed')) {
            $this->warn('Seeding tenant databases...');
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force',
            ]);
            $this->info('✔ Tenant seeders executed.');
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

        if (!$this->option('no-seed')) {
            $this->warn('Seeding central database...');
            Artisan::call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);
            $this->info('✔ Central seeders executed.');
        }
    }
}
