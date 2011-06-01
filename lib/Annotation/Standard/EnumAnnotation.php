<?php

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
