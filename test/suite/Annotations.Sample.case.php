<?php

namespace Sample;

use Annotation\Annotation;

/**
 * @usage('class'=>true)
 */
class SampleAnnotation extends Annotation
{
  public $test = 'ok';
}

/**
 * @Sample\Sample
 */
class SampleClass
{
}
