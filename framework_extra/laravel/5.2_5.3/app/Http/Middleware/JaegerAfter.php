<?php
namespace App\Http\Middleware;

use Closure;

class JaegerAfter
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        return $response;
    }

    public function terminate($request, $response)
    {
        $statusCode = $response->status();
        JaegerBefore::$span->setTags([
            'http.status_code' => $statusCode,
            'http.method' => $request->method(),
            'http.url' => $request->url(),
            'error' => $statusCode >= 400 AND $statusCode <= 599 ? false : true,
        ]);

        JaegerBefore::$span->log(['message' => 'API:' . $request->path() .' end !']);

        JaegerBefore::$span->finish();

        JaegerBefore::$jeagerConfig->flush();
    }
}