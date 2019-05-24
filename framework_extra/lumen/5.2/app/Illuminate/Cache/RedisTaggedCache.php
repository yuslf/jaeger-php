<?php

namespace App\Illuminate\Cache;

use Illuminate\Cache\TaggedCache;

class RedisTaggedCache extends TaggedCache
{
    const REFERENCE_KEY_FOREVER = 'forever_ref';

    const REFERENCE_KEY_STANDARD = 'standard_ref';

    public function put($key, $value, $minutes = null)
    {
        $this->pushStandardKeys($this->tags->getNamespace(), $key);

        parent::put($key, $value, $minutes);
    }

    public function forever($key, $value)
    {
        $this->pushForeverKeys($this->tags->getNamespace(), $key);

        parent::forever($key, $value);
    }

    public function flush()
    {
        $this->deleteForeverKeys();
        $this->deleteStandardKeys();

        parent::flush();
    }

    protected function pushStandardKeys($namespace, $key)
    {
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_STANDARD);
    }

    protected function pushForeverKeys($namespace, $key)
    {
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_FOREVER);
    }

    protected function pushKeys($namespace, $key, $reference)
    {
        $fullKey = $this->getPrefix().sha1($namespace).':'.$key;

        foreach (explode('|', $namespace) as $segment) {
            //$this->store->connection()->sadd($this->referenceKey($segment, $reference), $fullKey);
            $this->store->getRedis()->sadd($this->referenceKey($segment, $reference), $fullKey);
        }
    }

    protected function deleteForeverKeys()
    {
        $this->deleteKeysByReference(self::REFERENCE_KEY_FOREVER);
    }

    protected function deleteStandardKeys()
    {
        $this->deleteKeysByReference(self::REFERENCE_KEY_STANDARD);
    }

    protected function deleteKeysByReference($reference)
    {
        foreach (explode('|', $this->tags->getNamespace()) as $segment) {
            $this->deleteValues($segment = $this->referenceKey($segment, $reference));

            //$this->store->connection()->del($segment);
            $this->store->getRedis()->del($segment);
        }
    }

    protected function deleteValues($referenceKey)
    {
        //$values = array_unique($this->store->connection()->smembers($referenceKey));
        $values = array_unique($this->store->getRedis()->smembers($referenceKey));

        if (count($values) > 0) {
            //call_user_func_array([$this->store->connection(), 'del'], $values);
            call_user_func_array([$this->store->getRedis(), 'del'], $values);
        }
    }

    protected function referenceKey($segment, $suffix)
    {
        return $this->getPrefix().$segment.':'.$suffix;
    }
}
