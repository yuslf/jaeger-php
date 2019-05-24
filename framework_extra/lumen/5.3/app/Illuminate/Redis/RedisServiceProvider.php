<?php

namespace App\Illuminate\Redis;

use Illuminate\Redis\RedisServiceProvider AS BaseRedisServiceProvider;

class RedisServiceProvider extends BaseRedisServiceProvider
{
    public function register()
    {
        $this->app->singleton('redis', function ($app) {
            return new Database($app['config']['database.redis']);
        });
    }
}
