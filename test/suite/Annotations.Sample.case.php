<?php

namespace Sample;

use Mindplay\Annotation\Annotation;

/**
 * @usage('class'=>true)
 */
class SampleAnnotation extends Annotation
{
  public $test = 'ok';
}

class DefaultSampleAnnotation extends SampleAnnotation
{}
