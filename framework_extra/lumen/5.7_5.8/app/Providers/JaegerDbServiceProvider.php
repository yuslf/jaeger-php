<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\JaegerBefore;
use OpenTracing\Formats;

class JaegerDbServiceProvider extends ServiceProvider
{
    public function boot()
    {
        app('redis')->enableEvents();
        app('redis')->listen(function($query) {
            $inject = [];
            $spanCtx = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, JaegerBefore::$inject);
            $span = JaegerBefore::$tracer->startSpan('RedisOperate', ['child_of' => $spanCtx]);
            JaegerBefore::$tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);

            $config = config('database.redis.' . $query->connectionName);
            $dsn = 'redis://' . $config['host'] . ':' . $config['port'] . '/' . $config['database'];
            $span->setTags([
                'db.instance' => $dsn,
                'db.type' => 'redis',
                'db.statement' => $query->command . ' -- parameters:' . json_encode($query->parameters),
            ]);

            $span->log(['message' => "Redis Operate: [". $dsn .'] '. $query->command .'[' . $query->time . '] end !']);
            $span->finish();
        });

        app('db')->listen(function($query) {
            $inject = [];
            $spanCtx = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, JaegerBefore::$inject);
            $span = JaegerBefore::$tracer->startSpan('MysqlQuery', ['child_of' => $spanCtx]);
            JaegerBefore::$tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);

            $config = $query->connection->getConfig();
            $dsn = $config['driver'] . '://' . $config['host'] . ':' . $config['port'] . '/' . $config['database'];

            $span->setTags([
                'db.instance' => $dsn,
                'db.type' => $config['driver'],
                'db.user' => $config['username'],
                'db.statement' => $query->sql . ' -- bindings:' . json_encode($query->bindings),
            ]);

            $span->log(['message' => "Mysql Query: [". $dsn .'] '. $query->sql .' end !']);
            $span->finish();
        });
    }

    public function register()
    {
        //
    }
}