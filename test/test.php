<?php

## Configure PHP include paths

set_include_path(
  dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib'
  .PATH_SEPARATOR . dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'lib'
  .PATH_SEPARATOR . dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'annotations'
);

## Configure a simple auto-loader

spl_autoload_register(
  function($name)
  {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php';
    
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir)
    {
      $file = $dir.DIRECTORY_SEPARATOR.$path;
      if (file_exists($file))
        return require $file;
    }
    
    echo "File not found:\n";
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir)
    {
      $file = $dir.DIRECTORY_SEPARATOR.$path;
      echo (file_exists($file) ? '+' : '-') . $file . "\n";
    }
    
    throw new Exception("Error loading '{$path}'");
  },
  true, // throw exceptions on error
  true  // prepend autoloader
);

$runner = new xTestRunner(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'lib');

$runner->run(dirname(__FILE__).DIRECTORY_SEPARATOR.'suite'.DIRECTORY_SEPARATOR.'*.test.php');
