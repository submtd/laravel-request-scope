<?php

namespace Submtd\LaravelRequestScope\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelRequestScopeServiceProvider extends ServiceProvider
{
    /**
     * boot method
     */
    public function boot()
    {
        // config files
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-request-scope.php', 'laravel-request-scope');
        $this->publishes([__DIR__ . '/../../config' => config_path()], 'config');
    }
}
