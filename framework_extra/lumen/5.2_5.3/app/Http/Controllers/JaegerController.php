<?php
namespace App\Http\Controllers;

use App\Events\JaegerStartSpan;
use App\Helper\HttpClientHelper;
use App\Extra\Predis\Client;

class JaegerController extends Controller
{
    public function index()
    {
        $redisConf = config('database.redis');

        $a = new Client($redisConf['default']);

        var_dump($a->get('jaegerint2'));

        app('cache')->increment('jaegerint');

        var_dump(app('cache')->get('jaegerint'));

        app('redis')->incrby('jaegerint2',1);

        var_dump(app('redis')->get('jaegerint2'));


        $results = app('db')->select("SELECT 2222");

        var_dump($results);

        event(new JaegerStartSpan('jaeger test', 'hahahahhahahahaah'));

        $h = new HttpClientHelper();
        $url = 'http://www.baidu.com';
        $httpClientRes = [];
        try {
            $h = $h->GET($url);
            $httpClientRes['res'] = htmlspecialchars(substr($h->getBody(), 2000, 300));
        } catch (\Exception $e) {
            $httpClientRes['error'] = $e->getMessage();
        }
        var_dump($httpClientRes);


        return json_encode(['a' => 1, 'b' => 2]);
    }
}