<?php

## Configure PHP include paths

set_include_path(
  dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'lib'
);

## Configure a simple auto-loader

class Loader
{
  public $paths = array();
  
  public function load($name)
  {
    $names = explode('\\', $name, 2);
    
    if (count($names) === 2)
    {
      if (!isset($this->paths[$names[0]]))
        throw new Exception('undefined namespace: ' . $names[0]);
      
      $path = $this->paths[$names[0]]
        . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $names[1]);
    }
    else
    {
      $path = $name;
    }
    
    $path .= '.php';
    
    if (!include($path))
      throw new Exception('class ' . $name . ' not found: ' . $path);
  }
}

$loader = new Loader;
$loader->paths['Mindplay'] = dirname(dirname(__FILE__));

spl_autoload_register(array($loader, 'load'), true, true);

$runner = new xTestRunner(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'Annotation');

$runner->run(dirname(__FILE__).DIRECTORY_SEPARATOR.'suite'.DIRECTORY_SEPARATOR.'*.test.php');
