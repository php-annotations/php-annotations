<?php

/**
 * Specifies the required data-type of a property.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class VarAnnotation extends Annotation implements IAnnotationParser
{
  /**
   * @var string Specifies the type of value (e.g. for validation, for
   * parsing or conversion purposes; case insensitive)
   *
   * The following type-names are recommended:
   *
   *   bool
   *   int
   *   float
   *   string
   *   mixed
   *   object
   *   resource
   *   array
   *   callback (e.g. array($object|$class, $method') or 'function-name')
   * 
   * The following aliases are also acceptable:
   * 
   *   number (float)
   *   res (resource)
   *   boolean (bool)
   *   integer (int)
   *   double (float)
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
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('type'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->type))
      throw new AnnotationException('VarAnnotation requires a type property');
    
    $this->type = strtolower($this->type);
  }
}
