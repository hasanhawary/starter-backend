<?php

namespace App\Tools\Subscription;

use Illuminate\Support\ServiceProvider;

/**
 * Subscription Tool Service Provider
 * 
 * Registers the Subscription tool in the container.
 */
class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     */
    public function register(): void
    {
        // Register the Subscription Manager as a singleton
        $this->app->singleton('subscription', function ($app) {
            return new SubscriptionManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Tool is ready
    }
}
