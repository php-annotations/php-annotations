<?php

/**
 * Simple auto-loader for test and demo scripts.
 */
class Loader
{
  public function __construct()
  {
    spl_autoload_register(array($this, 'load'), true, true);
  }
  
  public $paths = array();
  
  public function load($name)
  {
    $names = explode('\\', $name, 2);
    
    if (count($names) === 2) {
      if (!isset($this->paths[$names[0]])) {
        throw new Exception('undefined namespace: ' . $names[0]);
      }
      
      $path = $this->paths[$names[0]]
        . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $names[1]);
    } else {
      $path = $name;
    }
    
    $path .= '.php';
    
    @include $path;
  }
}
