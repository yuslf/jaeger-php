<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\JaegerBefore;
use OpenTracing\Formats;

class JaegerDbServiceProvider extends ServiceProvider
{
    public function boot()
    {
        app('db')->listen(function($query, $params, $time, $driver){
            $inject = [];
            $spanCtx = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, JaegerBefore::$inject);
            $span = JaegerBefore::$tracer->startSpan('MysqlQuery', ['child_of' => $spanCtx]);
            JaegerBefore::$tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);
            $config = config('database.connections.' . $driver);
            $tmp = explode(':', $config['host']);
            $config['host'] = $tmp[0];
            if (empty($tmp[1])) {
                $config['port'] = 3306;
            } else {
                $config['port'] = $tmp[1];
            }
            $dsn = $config['driver'] . '://' . $config['host'] . ':' . $config['port'] . '/' . $config['database'];
            $span->setTags([
                'db.instance' => $dsn,
                'db.type' => $config['driver'],
                'db.user' => $config['username'],
                'db.statement' => $query . ' -- bindings:' . json_encode($params),
            ]);
            $span->log(['message' => "Mysql Query: [". $dsn .'] '. $query .' time:' . $time . ' end !']);
            $span->finish();
        });
    }

    public function register()
    {
        //
    }
}