<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Jaeger\Config;
use OpenTracing\Formats;

class JeagerTracer {

    protected $config = [
        'service_name' => 'JaegerCIService',
        'service_version' => '0.0.1',
        'collector' => '127.0.0.1:6831'
    ];

    protected $jeagerConfig;

    protected $tracer;

    protected $inject;

    protected $span;

    protected $flushed;

    public function __construct()
    {
        $ci = & get_instance();
        if ($ci->config->load('jeager', TRUE, TRUE)) {
            $this->config = array_merge($this->config, $ci->config->config['jeager']);
        }

        if (is_null($this->jeagerConfig)) {
            $this->jeagerConfig = Config::getInstance();
            $this->jeagerConfig->gen128bit();
        }

        if (is_null($this->tracer)) {
            $this->tracer = $this->jeagerConfig->initTrace($this->config['service_name'], $this->config['collector']);
        }

        if (is_null($this->inject)) {
            $this->inject = [];
        }

        $this->flushed = false;
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

    protected function getStatusCode()
    {
        $statusCode = null;

        $list = headers_list();
        foreach ($list as $r)
        {
            $r = explode(':', $r);
            if (count($r) < 2) {
                continue;
            }
            if ('status' == strtolower(trim($r[0])) AND intval(trim($r[1])) > 0) {
                $statusCode = intval(trim($r[1]));
                break;
            }
        }
        if (is_null($statusCode)) {
            $statusCode = 200;
        }
        return $statusCode;
    }

    public function getInject()
    {
        return $this->inject;
    }

    public function injectApiSpan()
    {
        unset($_SERVER['argv']);

        $spanContext = $this->tracer->extract(Formats\TEXT_MAP, $this->getCarrierByHeader());

        $this->span = $this->tracer->startSpan('API:' . $_SERVER['REQUEST_URI'], ['child_of' => $spanContext]);

        $this->span->addBaggageItem('version', $this->config['service_version']);

        $this->tracer->inject($this->span->getContext(), Formats\TEXT_MAP, $this->inject);
    }

    public function flush($error = null)
    {
        $statusCode = $this->getStatusCode();

        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        $this->span->setTags([
            'http.status_code' => $statusCode,
            'http.method' => $_SERVER['REQUEST_METHOD'],
            'http.url' => $url,
            'error' => $statusCode >= 400 AND $statusCode <= 599 AND is_null($error) ? false : true,
        ]);

        if ($error) {
            $log = [
                'event' => 'error',
                'message' => 'API:' . $_SERVER['REQUEST_URI'] .' Error: ' . $error
            ];
        } else {
            $log = ['message' => 'API:' . $_SERVER['REQUEST_URI'] .' end !'];
        }

        $this->span->log($log);

        $this->span->finish();

        $this->jeagerConfig->flush();

        $this->flushed = true;
    }

    public function injectSpan($jeagerSpan)
    {
        if ($this->flushed) {
            return;
        }

        $inject = [];
        $spanCtx = $this->tracer->extract(Formats\TEXT_MAP, $this->inject);
        $span = $this->tracer->startSpan($jeagerSpan->OperationName, ['child_of' => $spanCtx]);
        $this->tracer->inject($span->spanContext, Formats\TEXT_MAP, $inject);
        $span->setTags($jeagerSpan->Tag);
        $span->log($jeagerSpan->Log);
        $span->finish();
    }

}

/* End of file JeagerTracer.php */