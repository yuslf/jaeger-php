<?php

namespace App\Illuminate\Cache;

use Illuminate\Support\Arr;
use Illuminate\Cache\CacheManager AS BaseCacheManager;

class CacheManager extends BaseCacheManager
{
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = Arr::get($config, 'connection', 'default');

        return $this->repository(new RedisStore($redis, $this->getPrefix($config), $connection));
    }
}
