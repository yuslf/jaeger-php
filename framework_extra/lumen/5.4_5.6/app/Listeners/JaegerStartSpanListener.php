<?php

namespace App\Listeners;

use App\Events\JaegerStartSpan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Middleware\JaegerBefore;
use OpenTracing\Formats;

class JaegerStartSpanListener implements ShouldQueue
{
    public function __construct()
    {

    }

    public function handle(JaegerStartSpan $event)
    {
        $inject = [];
        $spanCtx = JaegerBefore::$tracer->extract(Formats\TEXT_MAP, JaegerBefore::$inject);
        $span = JaegerBefore::$tracer->startSpan($event->OperationName, ['child_of' => $spanCtx]);
        JaegerBefore::$tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);
        $span->setTags($event->Tag);
        $span->log($event->Log);
        $span->finish();
    }
}
