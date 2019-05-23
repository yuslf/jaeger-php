<?php

namespace App\Illuminate\Redis;

use Illuminate\Redis\RedisManager AS BaseRedisManager;

class RedisManager extends BaseRedisManager
{
    protected function connector()
    {
        switch ($this->driver) {
            case 'predis':
                return new Connectors\PredisConnector;
            case 'phpredis':
                return new Connectors\PhpRedisConnector;
        }
    }
}
