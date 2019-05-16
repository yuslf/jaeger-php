<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class JeagerRedis
{
    protected $redis;

    protected $ci;

    protected $errMsg = '';

    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 0,
        'password' => '',
    ];

    protected $trans;

    protected function initConf()
    {
        $this->ci = & get_instance();

        if ( ! $this->ci->config->load('redis', TRUE, TRUE) OR ! is_array($this->ci->config->config['redis']) ) {
            $this->errMsg = 'Redis connection failed. Check your configuration.';
            return FALSE;
        }

        $this->config = array_merge($this->config, $this->ci->config->config['redis']);

        return TRUE;
    }

    protected function initRedis()
    {
        if ($this->redis)
        {
            try
            {
                $this->redis->ping();
            }
            catch (Exception $e)
            {
                $this->redis = NULL;
            }

            if ($this->redis)
            {
                return TRUE;
            }
        }

        $this->redis = new Redis();

        try
        {
            if ( ! $this->redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']))
            {
                $this->errMsg = 'Redis connection failed. Check your configuration.';
                return FALSE;
            }

            if ( ! empty($this->config['password']) AND ! $this->redis->auth($this->config['password']) )
            {
                $this->errMsg = 'Redis authentication failed.';
                return FALSE;
            }

            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            //$this->redis->setOption(Redis::OPT_PREFIX, 'my-prefix:');
        }
        catch (Exception $e)
        {
            $this->errMsg = 'Redis connection refused (' . $e->getMessage() . ')';
            return FALSE;
        }

        return TRUE;
    }

    public function __construct($init = FALSE)
    {
        if ($init)
        {
            $this->initConf();

            $this->initRedis();
        }
    }

    public function init()
    {
        if ( ! $this->initConf() )
        {
            return FALSE;
        }
        if ( ! $this->initRedis() )
        {
            return FALSE;
        }
        return TRUE;
    }

    /*public static function getInstance()
    {
        $redis = new JeagerRedis(FALSE);

        if ( ! $redis->initConf() ) {
            return FALSE;
        }
        if ( ! $redis->initRedis() ) {
            return FALSE;
        }

        return $redis;
    }*/

    public function getErrorMsg()
    {
        return $this->errMsg;
    }

    public function __call($name, $arguments)
    {
        if ( ! method_exists($this->redis, $name) )
        {
            return FALSE;
        }

        if ( in_array($name, ['_serialize', '_unserialize']) )
        {
            return call_user_func_array(array($this->redis, $name), $arguments);
        }

        if ('multi' == $name)
        {
            $this->trans = []; return FALSE;
        }

        if ('exec' == $name)
        {
            $cmd = json_encode($this->trans);
            $this->trans = NULL;
        }
        else if ( ! is_array($this->trans) )
        {
            $this->trans[] = ['method' => $name, 'args' => $arguments];
            return call_user_func_array(array($this->redis, $name), $arguments);
        }
        else
        {
            $cmd = json_encode(['method' => $name, 'args' => $arguments]);
        }

        $res = call_user_func_array(array($this->redis, $name), $arguments);

        $JeagerSpan = & load_class('JeagerSpan', 'libraries', '');
        $JeagerSpan->init()
                   ->setOperationName('Redis Access')
                   ->setRedisTag('Redis', $this->config['host'], $this->config['port'], $cmd);

        if (FALSE === $res)
        {
            $this->errMsg = $this->redis->getLastError();
            $JeagerSpan->setMessage($this->errMsg)->setError();
        } else {
            $JeagerSpan->setMessage('Done.');
        }

        $JeagerSpan->inject();

        return $res;
    }
}