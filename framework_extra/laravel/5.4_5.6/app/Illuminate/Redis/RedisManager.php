<?php

namespace App\Illuminate\Redis;

use App\Events\JaegerStartSpan;
use InvalidArgumentException;
use Illuminate\Contracts\Redis\Factory;

class RedisManager implements Factory
{
    protected $driver;

    protected $config;

    protected $connections;

    public function __construct($driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function connection($name = null)
    {
        $name = $name ?: 'default';

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        return $this->connections[$name] = $this->resolve($name);
    }

    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        $options = $this->config['options'] ?? [];

        if (isset($this->config[$name])) {
            return $this->connector()->connect($this->config[$name], $options);
        }

        if (isset($this->config['clusters'][$name])) {
            return $this->resolveCluster($name);
        }

        throw new InvalidArgumentException("Redis connection [{$name}] not configured.");
    }

    protected function resolveCluster($name)
    {
        $clusterOptions = $this->config['clusters']['options'] ?? [];

        return $this->connector()->connectToCluster(
            $this->config['clusters'][$name], $clusterOptions, $this->config['options'] ?? []
        );
    }

    protected function connector()
    {
        switch ($this->driver) {
            case 'predis':
                return new \Illuminate\Redis\Connectors\PredisConnector;
            case 'phpredis':
                return new \Illuminate\Redis\Connectors\PhpRedisConnector;
        }
    }

    public function connections()
    {
        return $this->connections;
    }

    protected function JaegerSpan($method, $parameters, $start, $error = null)
    {
        $time = round((microtime(true) - $start) * 1000, 2);

        $dsn = "redis://{$this->config['default']['host']}:{$this->config['default']['port']}/{$this->config['default']['database']}";

        $message = "Redis Operate: [{$dsn}] {$method}[{$time}] end!";

        $tag = [
            'db.instance' => $dsn,
            'db.type' => 'Redis',
            'db.statement' => $method . ' -- parameters:' . json_encode($parameters),
        ];

        event(new JaegerStartSpan('RedisOperate', $message, $tag, $error));
    }

    public function __call($method, $parameters)
    {
        $start = microtime(true);

        try{
            $res = $this->connection()->{$method}(...$parameters);
        }catch (\Exception $e) {
            $this->JaegerSpan($method, $parameters, $start, $e);
            throw $e;
        }

        $this->JaegerSpan($method, $parameters, $start);

        return $res;
    }
}
