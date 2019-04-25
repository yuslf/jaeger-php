<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Jeagerphpciextra extends CI_Controller
{
    public function index()
    {
        //$this->output->set_header("DDDD: EEEEE");
        //$this->output->set_status_header(444, 'ssssssssssssssssssss');

        echo '<pre>';

        $this->load->database();

        $query = $this->db->query('SELECT "TEST JEAGER-PHP CI2 EXTRA!!";');

        foreach ($query->result() as $row) {
            var_dump((array) $row);
        }

        $this->load->driver('cache', array('adapter' => 'redis'));
        $foo = $this->cache->get('foo2');
        $foo = intval($foo);
        var_dump($foo);
        $foo ++;
        $this->cache->save('foo2', $foo, 300);

        $this->cache->save('hahah3', [1,2,3,4,5,6]);
        var_dump($this->cache->get('hahah3'));

        $this->load->library('jeager_http_client');

        //var_dump($this->jeager_http_client->Url('http://127.0.0.1:8002/jaeger')->Port(8002)->GET()->Call());

        var_dump(substr($this->jeager_http_client->init()->Url('http://www.baidu.com')->GET()->Call(false), 200, 50));

        echo '</pre>';

        exit;
    }
}
