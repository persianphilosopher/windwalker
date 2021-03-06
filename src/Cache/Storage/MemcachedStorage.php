<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Cache\Storage;

use Psr\Cache\CacheItemInterface;
use Windwalker\Cache\Item\CacheItem;

/**
 * Class MemcachedStorage
 *
 * @since 2.0
 */
class MemcachedStorage extends AbstractDriverCacheStorage
{
    /**
     * Class init.
     *
     * @param   \Memcached $driver  The cache storage driver.
     * @param   int        $ttl     The Time To Live (TTL) of an item
     * @param   mixed      $options An options array, or an object that implements \ArrayAccess
     *
     * @throws \RuntimeException
     */
    public function __construct($driver = null, $ttl = null, $options = [])
    {
        if (!extension_loaded('memcached') || !class_exists('Memcached')) {
            throw new \RuntimeException('Memcached not supported.');
        }

        parent::__construct($driver, $ttl, $options);
    }

    /**
     * Method to determine whether a storage entry has been set for a key.
     *
     * @param   string $key The storage entry identifier.
     *
     * @return  boolean
     */
    public function exists($key)
    {
        $this->connect();

        $this->driver->get($key);

        return ($this->driver->getResultCode() != \Memcached::RES_NOTFOUND);
    }

    /**
     * Here we pass in a cache key to be fetched from the cache.
     * A CacheItem object will be constructed and returned to us
     *
     * @param string $key The unique key of this item in the cache
     *
     * @return CacheItemInterface  The newly populated CacheItem class representing the stored data in the cache
     * @throws \Exception
     */
    public function getItem($key)
    {
        if (!$this->exists($key)) {
            return new CacheItem($key);
        }

        $this->connect();

        $value = $this->driver->get($key);
        $code = $this->driver->getResultCode();

        $item = new CacheItem($key);

        if ($code === \Memcached::RES_SUCCESS) {
            $item->set($value);
        }

        return $item;
    }

    /**
     * Persisting our data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param CacheItemInterface $item The cache item to store.
     *
     * @return static Return self to support chaining
     */
    public function save(CacheItemInterface $item)
    {
        $this->connect();

        $ttl = $this->ttl;

        $this->driver->set($item->getKey(), $item->get(), $ttl);

        return (bool) ($this->driver->getResultCode() == \Memcached::RES_SUCCESS);
    }

    /**
     * Remove an item from the cache by its unique key
     *
     * @param string $key The unique cache key of the item to remove
     *
     * @return static Return self to support chaining
     */
    public function deleteItem($key)
    {
        $this->connect();

        $this->driver->delete($key);

        $rc = $this->driver->getResultCode();

        if (($rc != \Memcached::RES_SUCCESS)) {
            return $this;
        }

        return $this;
    }

    /**
     * This will wipe out the entire cache's keys
     *
     * @return static Return self to support chaining
     */
    public function clear()
    {
        return $this->driver->connect()->flush();
    }

    /**
     * connect
     *
     * @return  static
     */
    protected function connect()
    {
        // We want to only create the driver once.
        if ($this->driver) {
            return $this;
        }

        $this->driver = new \Memcached();

        $this->driver->setOption(\Memcached::OPT_COMPRESSION, false);
        $this->driver->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        return $this;
    }
}
