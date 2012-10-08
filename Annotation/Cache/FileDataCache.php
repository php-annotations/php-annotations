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

/**
 * This cache-provider stores annotation-data in PHP files.
 */
class FileDataCache implements IDataCache
{
    /**
     * @var string The PHP opening tag (used when writing cache files)
     */
    const PHP_TAG = "<?php\n\n";

    /**
     * @var int The file mode used when creating new cache files
     */
    private $_fileMode;

    /**
     * @var string Absolute path to a folder where cache files will be created
     */
    private $_path;

    /**
     * Initializes the file cache-provider
     *
     * @param string $path absolute path to a folder where cache files will be created
     * @param int $fileMode
     */
    public function __construct($path, $fileMode = 0777)
    {
        $this->_path = $path;
        $this->_fileMode = $fileMode;
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
        return file_exists($this->_getPath($key));
    }

    /**
     * Caches the given data with the given key.
     *
     * @param string $key cache key
     * @param array $data the data to be cached
     * @see IDataCache
     */
    public function store($key, $data)
    {
        $path = $this->_getPath($key);

        $content = self::PHP_TAG . 'return ' . var_export($data, true) . ";\n";

        if (@file_put_contents($path, $content, LOCK_EX) === false) {
            throw new AnnotationException("unable to write cache file: $path");
        }

        if (@chmod($path, $this->_fileMode) === false) {
            throw new AnnotationException("unable to set permissions of cache file: $path");
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
        return include($this->_getPath($key));
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
        return filemtime($this->_getPath($key));
    }

    /**
     * Maps a cache-key to the absolute path of a PHP file
     *
     * @param string $key cache key
     * @return string absolute path of the PHP file
     */
    private function _getPath($key)
    {
        return $this->_path . DIRECTORY_SEPARATOR . $key . '.annotations.php';
    }

    /**
     * @return string absolute path of the folder where cache files are created
     */
    public function getPath()
    {
        return $this->_path;
    }
}
