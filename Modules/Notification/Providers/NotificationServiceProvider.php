<?php

namespace Modules\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Notification\app\Console\SendNotificationReminder;
use Modules\Notification\app\Listeners\SyncScheduleEventsWithSourceListener;
use Modules\Notification\app\Tools\NotificationManager;
use Modules\Notification\Tools\Services\Notification\NotificationEventService;
use Modules\Notification\Tools\Services\Notification\SystemEventService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NotificationServiceProvider extends ServiceProvider
{
    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerScheduleEventCleanup();
    }

    /**
     * Bind the calendar / reminder cleanup to every model's delete and restore.
     *
     * A schedule event can be raised for any model a system event fires on, so
     * the listener is registered against Eloquent's wildcard model events
     * rather than per model. New system events are covered automatically.
     */
    protected function registerScheduleEventCleanup(): void
    {
        // Shared, because the listener carries what `deleting` read across to
        // `deleted`, and the dispatcher resolves a class listener per event.
        $this->app->singleton(SyncScheduleEventsWithSourceListener::class);

        Event::listen('eloquent.deleting: *', [SyncScheduleEventsWithSourceListener::class, 'handleDeleting']);
        Event::listen('eloquent.deleted: *', [SyncScheduleEventsWithSourceListener::class, 'handleDeleted']);
        Event::listen('eloquent.restored: *', [SyncScheduleEventsWithSourceListener::class, 'handleRestored']);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        require_once __DIR__.'/../app/Helpers/helpers.php';

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton('notification', function ($app) {
            return new NotificationManager(
                $app->make(SystemEventService::class),
                $app->make(NotificationEventService::class),
            );
        });

    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            SendNotificationReminder::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('notification:reminder')->hourly()->withoutOverlapping();
        });
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
            $this->loadTranslationsFrom(__DIR__.'/../lang', $this->nameLower);
            $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__.'/../config';

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
        $sourcePath = __DIR__.'/../resources/views';

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
}
