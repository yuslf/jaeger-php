<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class JaegerStartSpan
{
    use SerializesModels;

    const SPAN_KIND_CLIENT = 'client';
    const SPAN_KIND_SERVER = 'server';
    const SPAN_KIND_PRODUCER = 'producer';
    const SPAN_KIND_CONSUMER = 'consumer';

    public $OperationName;

    public $Tag;

    public $Log;

    public function setRpcTag($kind, $address, $hostname, $ipv4, $ipv6, $port, $service, $message_bus_destination = null)
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }
        $this->Tag['span.kind'] = $kind;
        $this->Tag['peer.address'] = $address;
        $this->Tag['peer.hostname'] = $hostname;
        $this->Tag['peer.ipv4'] = $ipv4;
        $this->Tag['peer.ipv6'] = $ipv6;
        $this->Tag['peer.port'] = $port;
        $this->Tag['peer.service'] = $service;

        if ($message_bus_destination) {
            $this->Tag['message_bus.destination'] = $message_bus_destination;
        }
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
    }

    public function setError($ErrorObj, $ErrorKind, $ErrorStack)
    {
        if (is_null($this->Tag)) {
            $this->Tag = [];
        }
        if (is_null($this->Log)) {
            $this->Log = [];
        }
        $this->Tag['error'] = true;
        $this->Log['event'] = 'error';
        $this->Log['stack'] = $ErrorStack;
        $this->Log['error.object'] = $ErrorObj;
        $this->Log['error.kind'] = $ErrorKind;
    }

    public function setOperationName($OperationName)
    {
        $this->OperationName = $OperationName;
    }

    public function setMessage($Message)
    {
        if (is_null($this->Log)) {
            $this->Log = [];
        }
        $this->Log['message'] = $Message;
    }

    public function __construct($OperationName, $Message, $Tag = [], $ErrorObj = null, $ErrorKind = null, $ErrorStack = null)
    {
        $this->OperationName = $OperationName;

        if ($this->Log AND is_array($this->Log)) {
            $this->Log['message'] = $Message;
        } else {
            $this->Log = ['message' => $Message];
        }

        if ($Tag AND is_array($Tag)) {
            $this->Tag = $Tag;
        } else {
            $this->Tag = [];
        }

        if ($ErrorObj OR $ErrorKind OR $ErrorStack) {
            $this->setError($ErrorObj, $ErrorKind, $ErrorStack);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    /*public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }*/
}
