<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class CI_JeagerSpan {

    const SPAN_KIND_CLIENT = 'client';
    const SPAN_KIND_SERVER = 'server';
    const SPAN_KIND_PRODUCER = 'producer';
    const SPAN_KIND_CONSUMER = 'consumer';

    public $OperationName;

    public $Tag;

    public $Log;

    public function setRpcTag($kind, $address, $hostname, $port, $service, $ipv4 = null, $ipv6 = null, $message_bus_destination = null)
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }
        $this->Tag['span.kind'] = $kind;
        $this->Tag['peer.address'] = $address;
        $this->Tag['peer.hostname'] = $hostname;
        $this->Tag['peer.port'] = $port;
        $this->Tag['peer.service'] = $service;
        if ($ipv4) {
            $this->Tag['peer.ipv4'] = $ipv4;
        }
        if ($ipv6) {
            $this->Tag['peer.ipv6'] = $ipv6;
        }
        if ($message_bus_destination) {
            $this->Tag['message_bus.destination'] = $message_bus_destination;
        }

        return $this;
    }

    public function setDbTag($driver, $host, $port, $db, $user, $sql, $bindings = [])
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }

        $dsn = "{$driver}://{$host}:{$port}/{$db}";
        $this->Tag['db.instance'] = $dsn;
        $this->Tag['db.type'] = $driver;
        $this->Tag['db.user'] = $user;
        $this->Tag['db.statement'] = "{$sql} --bindings:" . json_encode($bindings);

        $this->Tag['span.kind'] = static::SPAN_KIND_CLIENT;
        $this->Tag['peer.address'] = $dsn;
        $this->Tag['peer.hostname'] = $host;
        $this->Tag['peer.port'] = $port;
        $this->Tag['peer.service'] = $driver;

        return $this;
    }

    public function setRedisTag($driver, $host, $port, $cmd)
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }

        $dsn = "{$driver}://{$host}:{$port}";
        $this->Tag['db.instance'] = $dsn;
        $this->Tag['db.type'] = $driver;
        $this->Tag['db.statement'] = $cmd;

        $this->Tag['span.kind'] = static::SPAN_KIND_CLIENT;
        $this->Tag['peer.address'] = $dsn;
        $this->Tag['peer.hostname'] = $host;
        $this->Tag['peer.port'] = $port;
        $this->Tag['peer.service'] = $driver;

        return $this;
    }

    public function setError($ErrorObj = null, $ErrorKind = null, $ErrorStack = null)
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }
        if (is_null($this->Log)) {
            $this->Log = [];
        }
        $this->Tag['error'] = true;
        $this->Log['event'] = 'error';

        if (! is_null($ErrorObj)) {
            $this->Log['error.object'] = $ErrorObj;
        }
        if (! is_null($ErrorKind)) {
            $this->Log['error.kind'] = $ErrorKind;
        }
        if (! is_null($ErrorStack)) {
            $this->Log['stack'] = $ErrorStack;
        }
        return $this;
    }

    public function setOperationName($OperationName)
    {
        $this->OperationName = $OperationName;

        return $this;
    }

    public function setMessage($Message)
    {
        if (is_null($this->Log)) {
            $this->Log = [];
        }
        $this->Log['message'] = $Message;

        return $this;
    }

    public function init()
    {
        $this->OperationName = '';
        $this->Tag = [];
        $this->Log = [];

        return $this;
    }

    public function inject()
    {
        if (empty($this->OperationName) OR empty($this->Log) OR empty($this->Tag)) {
            return;
        }

        $JeagerTracer = & load_class('JeagerTracer', 'libraries', '');

        $JeagerTracer->injectSpan($this);
    }

    public function __construct()
    {
        $this->init();
    }

}

/* End of file JeagerSpan.php */