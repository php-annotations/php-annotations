<?php

require 'lib/xTest.php';
require 'lib/xTestRunner.php';

$runner = new xTestRunner(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'x');
$runner->run(dirname(__FILE__).DIRECTORY_SEPARATOR.'*.test.php');
