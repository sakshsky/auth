<?php

namespace Sakshsky\Auth;

use Illuminate\Support\ServiceProvider;
use Sakshsky\Auth\Commands\StartAuthMonitor;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/sakshsky-auth.php' => config_path('sakshsky-auth.php'),
        ], 'config');

        // Publish sample routes
        $this->publishes([
            __DIR__.'/routes.php' => base_path('routes/sakshsky-auth.php'),
        ], 'routes');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Register console command
        $this->commands([
            StartAuthMonitor::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sakshsky-auth.php', 'sakshsky-auth');
    }
}