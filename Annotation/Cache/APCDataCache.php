<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <https://github.com/mindplay-dk/php-annotations>
 */

namespace Mindplay\Annotation\Cache;

use Mindplay\Annotation\Cache\IDataCache;
use Mindplay\Annotation\AnnotationException;

use APCIterator;

/**
 * This cache-provider implements APC as a back-end for storing annotation-data.
 *
 * @todo add unit test
 */
class APCDataCache implements IDataCache
{
    /**
     * Check if APC is available.
     */
    public function __construct()
    {
        if (!class_exists('APCIterator')) {
            throw new AnnotationException('APC extension (version 3.1.1 or newer) not configured/loaded');
        }
    }

    /**
     * Check if annotation-data for the key has been stored.
     *
     * @param string $key cache key
     *
     * @return bool true if data with the given key has been stored; otherwise false
     * @see IDataCache
     */
    public function exists($key)
    {
        return apc_exists($key);
    }

    /**
     * Caches the given data with the given key.
     *
     * @param string $key cache key
     * @param mixed $data the data to be cached
     * @see IDataCache
     */
    public function store($key, $data)
    {
        if (apc_store($key, array('time'=>time(), 'data'=>$data) === false)) {
            throw new AnnotationException("unable to write APC cache entry with key: $key");
        }
    }

    /**
     * Fetches data stored for the given key.
     *
     * @param string $key cache key
     * @return mixed the cached data
     * @see IDataCache
     */
    public function fetch($key)
    {
        $array = apc_fetch($key);

        return $array['data'];
    }

    /**
     * Returns the timestamp of the last cache update for the given key.
     *
     * @param string $key cache key
     * @return int unix timestamp
     * @see IDataCache
     */
    public function getTimestamp($key)
    {
        $array = apc_fetch($key);

        return $array['time'];
    }
}
