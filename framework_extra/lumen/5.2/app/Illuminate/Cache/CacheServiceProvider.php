<?php

namespace App\Illuminate\Cache;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Cache\Console\ClearCommand;

class CacheServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;
        });

        $this->registerCommands();
    }

    public function registerCommands()
    {
        $this->app->singleton('command.cache.clear', function ($app) {
            return new ClearCommand($app['cache']);
        });

        $this->commands('command.cache.clear');
    }

    public function provides()
    {
        return [
            'cache', 'cache.store', 'memcached.connector', 'command.cache.clear',
        ];
    }
}
