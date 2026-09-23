<?php

namespace Modules\Showcase\app\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Observers\ShowcaseObserver;
use Modules\Showcase\app\Policies\ShowcasePolicy;
use Modules\Showcase\app\Services\ShowcaseService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Registers only what the module owns: its config, translations, migrations,
 * its single service, and the observer/policy of its main model. This is an
 * API-only backend, so no views, assets, commands or schedules are wired here.
 */
class ShowcaseServiceProvider extends ServiceProvider
{
    protected string $name = 'Showcase';

    protected string $nameLower = 'showcase';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Showcase::observe(ShowcaseObserver::class);
        Gate::policy(Showcase::class, ShowcasePolicy::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ShowcaseService::class);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $key = ($config === 'config.php') ? $this->nameLower : $this->nameLower.'.'.$configKey;

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFrom($file->getPathname(), $key);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
