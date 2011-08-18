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

/**
 * Defines the return-type of a function or method
 *
 * @usage('method'=>true, 'inherited'=>true)
 */
class ReturnAnnotation extends Annotation implements IAnnotationParser
{
  /**
   * @var string
   */
  public $type;
  
  /**
   * Parse the standard PHP-DOC @var annotation
   */
  public static function parseAnnotation($value)
  {
    $parts = explode(' ', trim($value), 2);
    
    return array('type' => array_shift($parts));
  }
  
  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('type'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->type)) {
      throw new AnnotationException('ReturnAnnotation requires a type property');
    }
  }
}
