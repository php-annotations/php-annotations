<?php

## Configure PHP include paths

set_include_path(
  dirname(dirname(__FILE__))
  . DIRECTORY_SEPARATOR . 'test'
  . DIRECTORY_SEPARATOR . 'lib'
);

require 'lib/Loader.php';

$loader = new Loader;
$loader->paths['Mindplay'] = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Annotation';
$loader->paths['Sample'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'suite';

spl_autoload_register(array($loader, 'load'), true, true);

$runner = new xTestRunner(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'Annotation');

$runner->run(dirname(__FILE__).DIRECTORY_SEPARATOR.'suite'.DIRECTORY_SEPARATOR.'*.test.php');
