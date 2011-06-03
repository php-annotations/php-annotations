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

class DefaultSampleAnnotation extends SampleAnnotation
{}

/**
 * @Sample\Sample
 */
class SampleClass
{
}

/**
 * @DefaultSample
 */
class AnnotationInDefaultNamespace
{}

/**
 * @ignored
 */
class IgnoreMe
{}
