<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cache_redis extends CI_Driver {

    protected $_redis;

	// ------------------------------------------------------------------------

	public function get($id)
	{
        $data = $this->_redis->get($id);
        if ($data) {
            $data = $this->_redis->_unserialize($data);
        }
        return $data;
	}

	// ------------------------------------------------------------------------

	public function save($id, $data, $ttl = 60)
	{
        if ($ttl > 0) {
            $success = $this->_redis->setex($id, $ttl, $this->_redis->_serialize( $data ));
        } else {
            $success = $this->_redis->set($id, $this->_redis->_serialize( $data ));
        }

        return $success;
	}

	// ------------------------------------------------------------------------

	public function delete($id)
	{
        return $this->_redis->delete($id) === 1;
	}

    // ------------------------------------------------------------------------

    public function increment($id, $offset = 1)
    {
        return $this->_redis->incrBy($id, $offset);
    }

    // ------------------------------------------------------------------------

    public function decrement($id, $offset = 1)
    {
        return $this->_redis->decrBy($id, $offset);
    }

	// ------------------------------------------------------------------------

	public function clean()
	{
        return $this->_redis->flushDB();
	}

	// ------------------------------------------------------------------------

	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	public function get_metadata($id)
	{
		$stored = $this->_redis->get($id);

		if (count($stored) !== 3)
		{
			return FALSE;
		}

		list($data, $time, $ttl) = $stored;

		return array(
			'expire'	=> $time + $ttl,
			'mtime'		=> $time,
			'data'		=> $data
		);
	}

	// ------------------------------------------------------------------------

	private function _setup_redis()
	{
        $this->_redis = & load_class('JeagerRedis', 'libraries', '');

        if ( ! $this->_redis->init() )
        {
            log_message('error', 'Cache: ' . $this->_redis->getErrorMsg());
        }
	}

	// ------------------------------------------------------------------------

	public function is_supported()
	{
		if ( ! extension_loaded('redis'))
		{
			log_message('error', 'The Redis Extension must be loaded to use Redis Cache.');
			return FALSE;
		}

		$this->_setup_redis();
		return TRUE;
	}

    // ------------------------------------------------------------------------

    public function __destruct()
    {
        if ( $this->_redis )
        {
            $this->_redis->close();
        }
    }
}

/* End of file Cache_redis.php */
/* Location: ./application/libraries/Cache/drivers/Cache_redis.php */