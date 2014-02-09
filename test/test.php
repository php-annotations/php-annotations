<?php
define('FULL_PATH', realpath(__DIR__ . '/..'));

$vendor_path = FULL_PATH . '/vendor';

if (!is_dir($vendor_path)) {
    echo 'Install dependencies first' . PHP_EOL;
    exit(1);
}

require_once($vendor_path . '/autoload.php');

$auto_loader = new \Composer\Autoload\ClassLoader();
$auto_loader->add("test\\", FULL_PATH);
$auto_loader->add("Sample\\", FULL_PATH . '/test/suite');
$auto_loader->register();

$runner = new \test\lib\xTestRunner(dirname(dirname(__FILE__)) . '/mindplay/annotations');
$runner->run(dirname(__FILE__).DIRECTORY_SEPARATOR.'suite'.DIRECTORY_SEPARATOR.'*.test.php');
