<?php

namespace App\Illuminate\Redis;

use Closure;
use Predis\Client;
use Illuminate\Support\Arr;
use App\Events\JaegerStartSpan;
use Illuminate\Contracts\Redis\Database as DatabaseContract;

class Database implements DatabaseContract
{
    protected $clients;

    protected $config;

    public function __construct(array $servers = [])
    {
        $cluster = Arr::pull($servers, 'cluster');

        $options = array_merge(['timeout' => 10.0], (array) Arr::pull($servers, 'options'));
        if ($cluster) {
            $this->clients = $this->createAggregateClient($servers, $options);
        } else {
            $this->clients = $this->createSingleClients($servers, $options);
        }
    }

    protected function createAggregateClient(array $servers, array $options = [])
    {
        $this->config = ['default' => array_values($servers)];
        return ['default' => new Client(array_values($servers), $options)];
    }

    protected function createSingleClients(array $servers, array $options = [])
    {
        $clients = [];
        $this->config = [];

        foreach ($servers as $key => $server) {
            $this->config[$key] = $server;
            $clients[$key] = new Client($server, $options);
        }

        return $clients;
    }

    public function connection($name = 'default')
    {
        return Arr::get($this->clients, $name ?: 'default');
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

    public function command($method, array $parameters = [])
    {
        $start = microtime(true);

        try{
            $res = call_user_func_array([$this->clients['default'], $method], $parameters);
        }catch (\Exception $e) {
            $this->JaegerSpan($method, $parameters, $start, $e);
            throw $e;
        }

        $this->JaegerSpan($method, $parameters, $start);

        return $res;
    }

    public function subscribe($channels, Closure $callback, $connection = null, $method = 'subscribe')
    {
        $loop = $this->connection($connection)->pubSubLoop();

        call_user_func_array([$loop, $method], (array) $channels);

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                call_user_func($callback, $message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    public function psubscribe($channels, Closure $callback, $connection = null)
    {
        return $this->subscribe($channels, $callback, $connection, __FUNCTION__);
    }

    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }
}
