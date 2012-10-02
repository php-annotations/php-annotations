<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <http://code.google.com/p/php-annotations>
 */

namespace Mindplay\Annotation\Cache;

use \Mindplay\Annotation\Core\AnnotationException;
use \ReflectionClass;
use \APCIterator;

/**
 * APC cache provider
 *
 * Stores the class metadata on APC
 *
 * @author Luís Otávio Cobucci Oblonczyk
 */
class AnnotationApcCache implements IAnnotationCache
{
    /**
     * Returns if the identifier exists on the storage
     *
     * @param string $id
     *
     * @return boolean
     */
    public function exists($id)
    {
        return apc_exists($id);
    }

    /**
     * Stores the content
     *
     * @param string $id
     * @param string $content
     */
    public function store($id, $content)
    {
        if (apc_store($id, $content) === false) {
            throw new AnnotationException(__METHOD__ . ' : error writing cache ' . $id);
        }
    }

    /**
     * Retrieves the content
     *
     * @param string $id
     *
     * @return mixed
     */
    public function get($id)
    {
        $content = apc_fetch($id);

        return eval($content);
    }

    /**
     * Returns the last change time
     *
     * @param string $id
     *
     * @return int
     */
    public function getLastChangeTime($id)
    {
        $info = new APCIterator(
            'user',
            sprintf('`^%s$`', preg_quote($id)),
            APC_ITER_MTIME,
            100,
            APC_LIST_ACTIVE
        );

        foreach ($info as $cache) {
            return $cache['mtime'];
        }
    }

    /**
     * Creates an ID for the storage from class name
     *
     * @param string $className
     *
     * @return string
     */
    public function createId(ReflectionClass $class)
    {
        return $class->getName() . '-annotations';
    }
}
