<?php

namespace App\Extra;

use Laravel\Lumen\Application AS BaseApplication;

class Application extends BaseApplication
{
    protected static $aliasesExtraRegistered = false;

    protected function registerCacheBindings()
    {
        $this->singleton('cache', function () {
            return $this->loadComponent('cache', 'App\Illuminate\Cache\CacheServiceProvider');
        });

        $this->singleton('cache.store', function () {
            return $this->loadComponent('cache', 'App\Illuminate\Cache\CacheServiceProvider', 'cache.store');
        });
    }

    public function withFacades()
    {
        parent::withFacades();

        if (! static::$aliasesExtraRegistered) {
            static::$aliasesExtraRegistered = true;

            class_alias('App\Facades\HttpClient', 'HttpClient');
        }
    }

}
