<?php

namespace App\Illuminate\Cache;

use Illuminate\Cache\TagSet;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Store;
use App\Illuminate\Redis\Database as Redis;

class RedisStore extends TaggableStore implements Store
{
    protected $redis;

    protected $prefix;

    protected $connection;

    public function __construct(Redis $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->connection = $connection;
    }

    public function get($key)
    {
        /*if (! is_null($value = $this->connection()->get($this->prefix.$key))) {
            return is_numeric($value) ? $value : unserialize($value);
        }*/
        if (! is_null($value = $this->redis->get($this->prefix.$key))) {
            return is_numeric($value) ? $value : unserialize($value);
        }
    }

    public function put($key, $value, $minutes)
    {
        $value = is_numeric($value) ? $value : serialize($value);

        $minutes = max(1, $minutes);

        //$this->connection()->setex($this->prefix.$key, $minutes * 60, $value);
        $this->redis->setex($this->prefix.$key, $minutes * 60, $value);
    }

    public function increment($key, $value = 1)
    {
        return $this->redis->incrby($this->prefix.$key, $value);
        //return $this->connection()->incrby($this->prefix.$key, $value);
    }

    public function decrement($key, $value = 1)
    {
        //return $this->connection()->decrby($this->prefix.$key, $value);
        return $this->redis->decrby($this->prefix.$key, $value);
    }

    public function forever($key, $value)
    {
        $value = is_numeric($value) ? $value : serialize($value);

        //$this->connection()->set($this->prefix.$key, $value);
        $this->redis->set($this->prefix.$key, $value);
    }

    public function forget($key)
    {
        //return (bool) $this->connection()->del($this->prefix.$key);
        return (bool) $this->redis->del($this->prefix.$key);
    }

    public function flush()
    {
        //$this->connection()->flushdb();
        $this->redis->flushdb();
    }

    public function tags($names)
    {
        return new RedisTaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }
}
