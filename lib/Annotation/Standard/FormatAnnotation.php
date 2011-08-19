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

namespace Annotation\Lib;

use Annotation\Annotation;
use Annotation\AnnotationException;

/**
 * Specifies how to display or format a property value (for display-purposes).
 */
class FormatAnnotation extends Annotation
{
  /**
   * @var $format string A formatting string, compatible with sprintf()
   * @see http://php.net/sprintf
   */
  public $format;
  
  /**
   * @var $default string String to be used in place of an empty property value.
   */
  public $default;
  
  /**
   * @var $callback mixed Standard PHP callback array (class name|object, method name) or function name.
   * This callback will be invoked with $format as the first argument, and the property value as the second argument.
   */
  public $callback = 'sprintf';
  
  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('format'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->format))
    {
      throw new AnnotationException('FormatAnnotation requires a format property');
    }
  }
}
