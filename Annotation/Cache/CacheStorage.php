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

use \ReflectionClass;

/**
 * Interface for cache providers
 *
 * All cache providers must implement this interface.
 *
 * @author Luís Otávio Cobucci Oblonczyk
 */
interface CacheStorage
{
  /**
   * Returns if the identifier exists on the storage
   *
   * @param string $id
   * @return boolean
   */
  public function exists($id);

  /**
   * Stores the content
   *
   * @param string $id
   * @param string $content
   */
  public function store($id, $content);

  /**
   * Retrieves the content
   *
   * @param string $id
   * @return mixed
   */
  public function get($id);

  /**
   * Returns the last change time
   *
   * @param string $id
   * @return int
   */
  public function getLastChangeTime($id);

  /**
   * Creates an ID for the storage from class name
   *
   * @param \ReflectionClass $className
   * @return string
   */
  public function createId(ReflectionClass $class);
}