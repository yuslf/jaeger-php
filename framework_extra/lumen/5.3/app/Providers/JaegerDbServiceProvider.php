<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\JaegerBefore;
use OpenTracing\Formats;

class JaegerDbServiceProvider extends ServiceProvider
{
    public function boot()
    {
        app('db')->listen(function($query) {
            $inject = [];
            $spanCtx = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, JaegerBefore::$inject);
            $span = JaegerBefore::$tracer->startSpan('MysqlQuery', ['child_of' => $spanCtx]);
            JaegerBefore::$tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);

            $config = [
                'driver' => $query->connection->getConfig('driver'),
                'host' => $query->connection->getConfig('host'),
                'port' => $query->connection->getConfig('port'),
                'database' => $query->connection->getConfig('database'),
                'username' => $query->connection->getConfig('username'),
            ];
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