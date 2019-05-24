<?php

namespace App\Illuminate\Redis;

use App\Extra\Predis\Client;
use Illuminate\Redis\Database AS BaseDatabase;

class Database extends BaseDatabase
{
    protected function createSingleClients(array $servers, array $options = [])
    {
        $clients = [];

        foreach ($servers as $key => $server) {
            $clients[$key] = new Client($server, $options);
        }

        return $clients;
    }

    protected function createAggregateClient(array $servers, array $options = [])
    {
        return ['default' => new Client(array_values($servers), $options)];
    }
}
