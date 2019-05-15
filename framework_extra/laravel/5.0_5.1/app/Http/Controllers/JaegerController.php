<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Facades\HttpClient;
use Illuminate\Support\Facades\Redis;
use App\Events\JaegerStartSpan;

class JaegerController extends Controller
{
    public function test()
    {
        $res = [];

        //测试httpclient
        $url = 'http://www.baidu.com';
        $httpClientRes = [];
        try {
            $h = HttpClient::GET($url);
            $httpClientRes['res'] = htmlspecialchars(substr($h->getBody(), 2000, 300));
        } catch (\Exception $e) {
            $httpClientRes['error'] = $e->getMessage();
        }
        $res['http_client'] = $httpClientRes;

        //测试数据库访问
        DB::insert('INSERT INTO `project`(`name`, `vcs`) VALUES(?, ?)', ['P1', 'p1.git']);
        $dbRes = DB::select('SELECT * FROM `project` WHERE 1 ORDER BY `id` DESC LIMIT 2');
        $res['db'] = $dbRes;


        //测试Redis访问
        $RedisRes = intval(Redis::get("jaeger:test"));
        $RedisRes ++;
        Redis::set("jaeger:test", $RedisRes);
        $res['redis'] = $RedisRes;


        //手动埋探针测试
        event(new JaegerStartSpan('jaeger test', 'hahahahhahahahaah'));

        $res['ok'] = true;

        return json_encode($res);
    }
}