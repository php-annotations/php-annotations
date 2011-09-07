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

namespace Mindplay\Annotation\Standard;

use Mindplay\Annotation\AnnotationException;
use Mindplay\Annotation\IAnnotationParser;
use Mindplay\Annotation\Annotation;

/**
 * Defines a method-parameter's type
 *
 * @usage('method'=>true, 'inherited'=>true, 'multiple'=>true)
 */
class ParamAnnotation extends Annotation implements IAnnotationParser
{
  /**
   * @var string
   */
  public $type;

  /**
   * @var string
   */
  public $name;

  /**
   * Parse the standard PHP-DOC @param annotation
   */
  public static function parseAnnotation($value)
  {
    $parts = explode(' ', trim($value), 3);

    return array('type' => $parts[0], 'name' => substr($parts[1], 1));
  }
  
  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('type', 'name'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->type)) {
      throw new AnnotationException('ParamAnnotation requires a type property');
    }
    
    if (!isset($this->name)) {
      throw new AnnotationException('ParamAnnotation requires a name property');
    }
  }
}
