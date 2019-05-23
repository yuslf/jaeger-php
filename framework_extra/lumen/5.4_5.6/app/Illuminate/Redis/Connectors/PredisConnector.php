<?php

namespace App\Illuminate\Redis\Connectors;

use App\Extra\Predis\Client;
use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;

class PredisConnector
{
    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );

        return new PredisConnection(new Client($config, $formattedOptions));
    }

    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        return new PredisClusterConnection(new Client(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }
}
