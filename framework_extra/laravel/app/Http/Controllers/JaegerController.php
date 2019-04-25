<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use App\Http\Middleware\JaegerBefore;
use Illuminate\Support\Facades\DB;
use App\Helper\HttpClientHelper;
use App\Facades\HttpClient;

class JaegerController extends Controller
{
    public function test()
    {
        $url = 'http://127.0.0.1:8002/jaeger';

        $client = new Client();

        $helper = new HttpClientHelper();

        try {
            //$res = $client->GET($url, ['headers' => JaegerBefore::$inject]);

            //$res = $helper->GET($url);

            $res = HttpClient::GET($url);

        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        DB::insert('INSERT INTO `project`(`name`, `vcs`) VALUES(?, ?)', ['P1', 'p1.git']);

        $dbRes = DB::select('SELECT * FROM `project` WHERE 1 ORDER BY `id` DESC LIMIT 2');

        return json_encode(['jaeger2' => strval($res->getBody()), 'db' => (array) $dbRes]);



        $test = intval(Redis::get("jaeger:test"));
        $test ++;
        Redis::set("jaeger:test:", $test);

        event(new JaegerStartSpan('jaeger test', 'hahahahhahahahaah'));

        return json_encode(['db' => Project::find(72), 'redis' => $test]);
        //return view('user.profile', ['user' => User::findOrFail($id)]);
        //return view('user.profile', ['user' => User::findOrFail($id)]);
    }
}