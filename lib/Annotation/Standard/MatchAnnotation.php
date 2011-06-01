<?php

/**
 * Specifies validation of a string against a regular expression pattern.
 */
class MatchAnnotation extends ValidationAnnotationBase
{
  /**
   * @var string The regular expression pattern to match against.
   */
  public $pattern;
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('pattern'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->pattern))
      throw new AnnotationException('PatternAnnotation requires a pattern property');
  }
}
