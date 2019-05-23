<?php

namespace App\Illuminate\Redis;

use Illuminate\Support\Arr;
use Illuminate\Redis\RedisServiceProvider AS BaseRedisServiceProvider;

class RedisServiceProvider extends BaseRedisServiceProvider
{
    public function register()
    {
        $this->app->singleton('redis', function ($app) {
            $config = $app->make('config')->get('database.redis');

            return new RedisManager(Arr::pull($config, 'client', 'predis'), $config);
        });

        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }
}
