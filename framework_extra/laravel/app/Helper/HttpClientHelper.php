<?php
namespace App\Helper;

use GuzzleHttp\Client;
use App\Events\JaegerStartSpan;
use App\Http\Middleware\JaegerBefore;

class HttpClientHelper extends Client
{
    protected function _eventJaegerStartSpan($url, $method, $message, $ErrorObj = null, $ErrorKind = null, $ErrorStack = null)
    {
        $u = parse_url($url);
        $event = new JaegerStartSpan('HttpClient - ' . $method, $message);
        if (empty($u['port'])) {
            $u['port'] = 80;
        }
        if (empty($u['path'])) {
            $u['path'] = '/';
        }
        $event->setRpcTag('client', $url, $u['host'], 'n/a', 'n/a', $u['port'], $u['path']);

        if ($ErrorObj OR $ErrorKind OR $ErrorStack) {
            $event->setError($ErrorObj, $ErrorKind, $ErrorStack);
        }
        event($event);
    }

    public function __call($method, $args)
    {
        if (substr($method, -5) === 'Async') {
            return parent::__call($method, $args);
        }

        if (JaegerBefore::$inject) {
            if (isset($args[1]) AND is_array($args[1])) {
                if (isset($args[1]['headers']) AND is_array($args[1]['headers'])) {
                    $args[1]['headers'] = array_merge($args[1]['headers'], JaegerBefore::$inject);
                } else {
                    $args[1]['headers'] = JaegerBefore::$inject;
                }
            } else {
                $args[1] = ['headers' => JaegerBefore::$inject];
            }
        }

        try {
            $res = parent::__call($method, $args);
        } catch (\Exception $e) {
            $this->_eventJaegerStartSpan($args[0], $method, $e->getMessage(), get_class($e), $e->getFile(), $e->getTraceAsString());
            throw $e;
        }

        if (! empty($args[2])) {
            $this->_eventJaegerStartSpan($args[0], $method, 'Done[' . $res->getStatusCode() . ']!');
        }

        return $res;
    }
}