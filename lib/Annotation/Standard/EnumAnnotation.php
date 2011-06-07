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
 * Specifies validation against a fixed enumeration of allowed values.
 */
class EnumAnnotation extends ValidationAnnotationBase
{
  /**
   * @var array A list of acceptable values, typically in key=>value format.
   */
  public $values;
  
  /**
   * @var boolean Indicates whether or not to use strict comparison (===)
   */
  public $strict=false;
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('values'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->values))
      throw new AnnotationException('EnumAnnotation requires a list of possible values');
  }
}
