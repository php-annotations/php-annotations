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

/**
 * Common interface for annotation-data cache implementations.
 */
interface IDataCache
{
    /**
     * Check if annotation-data for the key has been stored.
     *
     * @param string $key cache key
     * @return bool true if data with the given key has been stored; otherwise false
     */
    public function exists($key);

    /**
     * Caches the given data with the given key.
     *
     * @param string $key cache key
     * @param mixed $data the data to be cached
     */
    public function store($key, $data);

    /**
     * Fetches data stored for the given key.
     *
     * @param string $key cache key
     * @return mixed the cached data
     */
    public function fetch($key);

    /**
     * Returns the timestamp of the last cache update for the given key.
     *
     * @param string $key cache key
     * @return int unix timestamp
     */
    public function getTimestamp($key);
}
