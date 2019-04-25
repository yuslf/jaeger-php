<?php
namespace App\Http\Middleware;

use Closure;

use Jaeger\Config;
use OpenTracing\Formats;

class JaegerBefore
{
    protected static $config;

    public static $jeagerConfig;

    public static $tracer;

    public static $span;

    public static $inject;

    public function __construct()
    {
        JaegerBefore::$config = config('jeager');

        JaegerBefore::$jeagerConfig = Config::getInstance();
        JaegerBefore::$jeagerConfig->gen128bit();

        JaegerBefore::$tracer = static::$jeagerConfig->initTrace('JaegerLaravelService1', '10.70.120.76:6831');

        JaegerBefore::$inject = [];
    }

    protected function getCarrierByHeader()
    {
        if (isset($_SERVER['HTTP_UBER_TRACE_ID']) AND isset($_SERVER['HTTP_UBERCTX_VERSION'])) {
            return [
                'UBER-TRACE-ID' => $_SERVER['HTTP_UBER_TRACE_ID'],
                'UBERCTX-VERSION' => $_SERVER['HTTP_UBERCTX_VERSION'],
            ];
        } else {
            return $_SERVER;
        }
    }

    public function handle($request, Closure $next)
    {
        unset($_SERVER['argv']);
        $spanContext = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, $this->getCarrierByHeader());

        JaegerBefore::$span = JaegerBefore::$tracer->startSpan('API:' . $request->path(), ['child_of' => $spanContext]);
        JaegerBefore::$span->addBaggageItem('version', '0.0.1');

        JaegerBefore::$tracer->inject(JaegerBefore::$span->getContext(), Formats\TEXT_MAP, JaegerBefore::$inject);

        return $next($request);
    }

    /*public function terminate($request, $response)
    {

    }*/
}