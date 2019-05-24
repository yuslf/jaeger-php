<?php

namespace App\Extra\Predis;

use App\Events\JaegerStartSpan;
use Predis\Client AS BaseClient;

class Client extends BaseClient
{
    protected $config;

    public function __construct($parameters = null, $options = null)
    {
        if (empty($parameters)) {
            $parameters = $this->config = config('database.redis.default');
        } else if (is_array($parameters) and isset($parameters['host'])
            and isset($parameters['port']) and isset($parameters['database'])){
            $this->config = $parameters;
        }
        parent::__construct($parameters, $options);
    }

    public function __call($commandID, $arguments)
    {
        $start = microtime(true);

        try{
            $res = $this->executeCommand(
                $this->createCommand($commandID, $arguments)
            );
        }catch (\Exception $e) {
            $this->JaegerSpan($commandID, $arguments, $start, $e);
            throw $e;
        }

        $this->JaegerSpan($commandID, $arguments, $start);

        return $res;
    }

    protected function JaegerSpan($method, $parameters, $start, $error = null)
    {
        if (empty($this->config)) {
            return null;
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        $dsn = "redis://{$this->config['host']}:{$this->config['port']}/{$this->config['database']}";

        $message = "Redis Operate: [{$dsn}] {$method}[{$time}] end!";

        $tag = [
            'db.instance' => $dsn,
            'db.type' => 'Redis',
            'db.statement' => $method . ' -- parameters:' . json_encode($parameters),
        ];

        event(new JaegerStartSpan('RedisOperate', $message, $tag, $error));
    }
}
