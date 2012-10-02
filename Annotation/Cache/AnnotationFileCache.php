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

use \Mindplay\Annotation\AnnotationException;
use \ReflectionClass;

/**
 * File cache provider
 *
 * Stores the class metadata on files
 *
 * @author Luís Otávio Cobucci Oblonczyk
 */
class AnnotationFileCache implements IAnnotationCache
{
  /**
   * @var string The PHP opening tag (used when writing cache files)
   */
  const PHP_TAG = "<?php\n\n";
  
  /**
   * @var int The file mode used when creating cache files
   */
  public $fileMode;
  
  /**
   * @var string Absolute path to a folder where cache files may be saved
   */
  public $path;
  
  /**
   * @var string Cache seed (can be used to disambiguate, if using multiple AnnotationManager instances with the same $cachePath)
   */
  public $seed;
  
  /**
   * Initializes the file cache
   *
   * @param string $path
   * @param string $seed
   * @param int $fileMode
   */
  public function __construct($path, $seed = '', $fileMode = 0777)
  {
    $this->path = $path;
    $this->seed = $seed;
    $this->fileMode = $fileMode;
  }
  
  /**
   * Returns if the identifier exists on the storage
   *
   * @param string $id
   * @return boolean
   */
  public function exists($id)
  {
    return file_exists($this->resolveCacheFile($id));
  }
  
  /**
   * Stores the content
   *
   * @param string $id
   * @param string $content
   */
  public function store($id, $content)
  {
    $file = $this->resolveCacheFile($id);

    $file_saved = @file_put_contents($file, self::PHP_TAG . $content, LOCK_EX) !== false;

    $mode_set = $file_saved && (@chmod($file, $this->fileMode) !== false);

    if ($file_saved===false || $mode_set===false) {
      throw new AnnotationException(__METHOD__ . ' : error writing cache file ' . $file);
    }
  }
  
  /**
   * Retrieves the content
   *
   * @param string $id
   * @return mixed
   */
  public function get($id)
  {
    return include $this->resolveCacheFile($id);
  }
  
  /**
   * Returns the last change time
   *
   * @param string $id
   * @return int
   */
  public function getLastChangeTime($id)
  {
    return filemtime($this->resolveCacheFile($id));
  }
  
  /**
   * Creates an ID for the storage from class name
   *
   * @param string $className
   * @return string
   */
  public function createId(ReflectionClass $class)
  {
    $path = $class->getFileName();
    
    return basename($path) . '-' . sprintf('%x', crc32($path . $this->seed)) . '.annotations.php';
  }
  
  /**
   * @param string $id
   * @return string
   */
  protected function resolveCacheFile($id)
  {
    return $this->path . DIRECTORY_SEPARATOR . $id;
  }
}