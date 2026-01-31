<?php

namespace App\Console\Commands\Setup;

use App\Trait\Setup\SetupModuleTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException as JsonExceptionAlias;
use Nwidart\Modules\Facades\Module;

class Setup extends Command
{
    use SetupModuleTrait;

    public string $db;
    public string $defaultConnection;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install
                            {--db-host=localhost : Database Host}
                            {--db-port=3306 : Port for the database}
                            {--db-database= : Name for the database}
                            {--db-username=root : Username for accessing the database}
                            {--db-password=root : Password for accessing the database, it can be blank}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installing the application via CLI';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws JsonExceptionAlias
     */
    public function handle(): void
    {
        //Set DefaultConnection as property
        $this->defaultConnection = config('database.default');

        //Start installation process
        $this->warn('Application installation started...');

        //Setup Default Configuration
        $this->copyEnvExampleToEnv();
        $this->updateEnvVariablesFromOptions();
        $this->info('Environment file created and updated Env.');

        //Generate application key
        Artisan::call('key:generate');
        $this->info('Application key generated.');

        //Create database
        $this->createDatabase();

        //Setup modules if Laravel Modules package is being used
        if ($this->isUsingLaravelModules()) {
            $this->setupModules();
        }

        //Link storage
        Artisan::call('storage:link');
        $this->info('Storage linked.');

        //Installation complete
        $this->info('Application installation completed successfully.');
        $this->displaySampleUserCredentials();
    }

    /**
     * Copy .env.example to .env if .env does not exist.
     *
     * @return void
     */
    private function copyEnvExampleToEnv(): void
    {
        if (!is_file(base_path('.env')) && is_file(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
        }
    }

    /**
     * Update environment variables from command options.
     *
     * @return void
     */
    private function updateEnvVariablesFromOptions(): void
    {
        $databaseName = empty($this->option('db-database')) ? time() . '_db' : $this->option('db-database');
        $this->db = $databaseName;

        updateDotEnv([
            'DB_HOST' => $this->option('db-host'),
            'DB_PORT' => $this->option('db-port'),
            'DB_DATABASE' => $databaseName,
            'DB_USERNAME' => $this->option('db-username'),
            'DB_PASSWORD' => $this->option('db-password'),
        ]);
    }

    /***
     * @return void
     */
    private function createDatabase(): void
    {
        // Get database connection configuration
        $config = config("database.connections.{$this->defaultConnection}");

        try {
            // Create a new PDO instance
            $pdo = new \PDO(
                "{$config['driver']}:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password']
            );

            // Create the database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->db}");
            $this->info('Database created.');

            // Update the configuration to use the new database
            $config['database'] = $this->db;
            config(["database.connections.{$this->defaultConnection}" => $config]);

            // Clear the configuration cache
            Artisan::call('config:clear');

            // Purge the old database connection
            DB::purge($this->defaultConnection);

            DB::purge('tenant');
            config(['database.connections.tenant.database' => $tenant->database]);
            config(['database.default' => 'tenant']);
            DB::reconnect('tenant');


            // Reconnect to the new database
            DB::reconnect($this->defaultConnection);

            // Check if the database connection is established
            if (DB::connection($this->defaultConnection)->getPdo()) {
                $this->info("Database connection reset successfully with ({$this->db}).");

                // Run migrations and seeders
                $this->warn('Running migrations and seeds...');

                Artisan::call('migrate:fresh');
                $this->info('Migrations executed.');

                Artisan::call('db:seed');
                $this->info('Seeders executed.');

            } else {
                $this->error('Failed to establish database connection.');
            }

        } catch (\PDOException $e) {
            // Drop the database if there is an error
            $pdo?->exec("DROP DATABASE IF EXISTS {$this->db}");
            $this->error('Error occurred when creating the database or running migrations: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            $this->error('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Check if Laravel Modules package is being used.
     *
     * @return bool
     * @throws JsonExceptionAlias
     */
    private function isUsingLaravelModules(): bool
    {
        //Read composer.json file to check for Laravel Modules package
        $composerJson = json_decode(File::get(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

        return isset($composerJson['require']['nwidart/laravel-modules']);
    }

    /**
     * Display sample user credentials.
     *
     * @return void
     */
    private function displaySampleUserCredentials(): void
    {
        $this->info('Sample user credentials:');
        $domain = Str::snake(config('brands.default_brand',config('app.name')));
        $this->table(['Name', 'Email', 'Password'], [['root', "root@$domain.com", '123456']]);
    }

    /**
     *  Setup all possible modules.
     *
     * @return void
     */
    private function setupModules(): void
    {
        collect(Module::all())->each(fn($mod) => $this->setupModule($mod->getName()));
    }
}
