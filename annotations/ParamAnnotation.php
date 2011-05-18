<?php

/**
 * Defines a method-parameter's type
 */
class ParamAnnotation extends Annotation implements IAnnotationParser
{
  /**
   * Parse the standard PHP-DOC @var annotation
   */
  public static function parseAnnotation($value)
  {}
  
  public function initAnnotation($properties)
  {}
}
