<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 * 
 * This software is licensed under the GNU LGPL license
 * for more information, please see: 
 * 
 * <https://github.com/mindplay-dk/php-annotations>
 */

namespace Mindplay\Annotation\Standard;

use Mindplay\Annotation\AnnotationException;

/**
 * Specifies validation of a property value against a fixed
 * enumeration of allowed values.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class EnumAnnotation extends ValidationAnnotationBase
{
  /**
   * @var array A list of acceptable values in key => value format.
   */
  public $values;
  
  /**
   * @var boolean Indicates whether or not to use strict comparison (===)
   */
  public $strict=false;
  
  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('values'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->values))
    {
      throw new AnnotationException('EnumAnnotation requires a list of possible values');
    }
  }
}
