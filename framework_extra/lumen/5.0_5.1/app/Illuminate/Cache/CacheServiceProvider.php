<?php

namespace App\Illuminate\Cache;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Cache\Console\CacheTableCommand;

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

        $this->app->singleton('command.cache.table', function ($app) {
            return new CacheTableCommand($app['files'], $app['composer']);
        });

        $this->commands('command.cache.clear', 'command.cache.table');
    }

    public function provides()
    {
        return [
            'cache', 'cache.store', 'memcached.connector', 'command.cache.clear', 'command.cache.table',
        ];
    }
}
