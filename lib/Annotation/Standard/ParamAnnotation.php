<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 * 
 * This software is licensed under the GNU LGPL license
 * for more information, please see: 
 * 
 * <http://code.google.com/p/php-annotations>
 */

namespace Annotation\Standard;

use Annotation\Annotation;
use Annotation\IAnnotationParser;

/**
 * Defines a method-parameter's type
 */
class ParamAnnotation extends Annotation implements IAnnotationParser
{
  /**
   * Parse the standard PHP-DOC @var annotation
   */
  public static function parseAnnotation($value)
  {
    return array();
  }
  
  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {}
}
