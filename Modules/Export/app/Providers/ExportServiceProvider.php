<?php

namespace Modules\Export\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Export\App\Models\ExportFile;
use Modules\Export\app\Policies\ExportPolicy;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ExportServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Export';

    protected string $nameLower = 'export';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->registerPolicies();

        $this->extendDiscoveryConfig();

        $this->mergeEnumTranslation();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Register Policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(ExportFile::class, ExportPolicy::class);
    }

    /**
     * Merge filters & sorting into the shared discovery config.
     */
    protected function extendDiscoveryConfig(): void
    {
        config([
            'discovery.filters' => array_merge(config('discovery.filters', []), config('export.discovery.filters', [])),
            'discovery.sorting' => array_merge(config('discovery.sorting', []), config('export.discovery.sorting', [])),
        ]);
    }

    /**
     * Merge enum translations to main enum file in lang.
     */
    protected function mergeEnumTranslation(): void
    {
        $this->mergeTranslations('enums');
    }

    /**
     * Generic helper to merge module translations into the application translator.
     *
     * @param string $fileName The name of the file (e.g., 'report' or 'enums')
     */
    protected function mergeTranslations(string $fileName): void
    {
        $langPath = module_path('Export', 'lang');

        if (! is_dir($langPath)) {
            return;
        }

        foreach (glob("{$langPath}/*/{$fileName}.php") as $filePath) {
            $locale = basename(dirname($filePath));
            $moduleTranslations = require $filePath;

            if (! is_array($moduleTranslations)) {
                continue;
            }

            $this->callAfterResolving('translator', function ($translator) use ($moduleTranslations, $locale, $fileName) {
                // Load existing app-level translations for this locale
                $appFile = lang_path("{$locale}/{$fileName}.php");
                $appTranslations = file_exists($appFile) ? require $appFile : [];

                // Deep merge: app translations override/extend module translations
                $merged = array_replace_recursive($appTranslations, $moduleTranslations);

                $translator->addLines(
                    $this->dotFlatten($merged, $fileName),
                    $locale
                );
            });
        }
    }

    /**
     * Flatten nested array into dot-notation keys with a prefix.
     * e.g. ['status' => ['pending' => 'Pending']]
     * becomes ['enum.status.pending' => 'Pending']
     */
    protected function dotFlatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->dotFlatten($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }
}
