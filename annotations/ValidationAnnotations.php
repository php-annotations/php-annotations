<?php

/**
 * Interface for data validation Annotations
 */
interface IValidationAnnotation
{
}

/**
 * Specifies a custom validation callback method.
 */
class ValidateAnnotation extends Annotation implements IValidationAnnotation
{
  /**
   * @var mixed An object, a class-name, or a function name.
   */
  public $type;
  
  /**
   * @var string Optional, identifies a class/object method.
   */
  public $method=null;
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('type', 'method'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->type))
      throw new AnnotationException('type property not set');
  }
  
  /**
   * @return mixed A standard PHP callback, e.g. an array($object, $method) pair, or a function name.
   */
  public function getCallback()
  {
    if ($this->method!==null)
      return array($this->type, $this->method);
    else
      return $this->type;
  }
}

/**
 * Specifies validation of various common property types.
 */
class TypeAnnotation extends Annotation implements IValidationAnnotation
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
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('type'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->type))
      throw new AnnotationException('TypeAnnotation requires a type property');
    
    $this->type = strtolower($this->type);
  }
}

/**
 * Specifies validation of object class.
 *
 * This may be used for other purposes besides validation, including
 * type declarations for dependency injection.
 */
class ClassAnnotation extends Annotation implements IValidationAnnotation
{
  /**
   * @var string The class-name to validate (case sensitive)
   */
  public $name;
  
  /**
   * @var bool If true, the class must match the validated value precisely.
   * Classes derived from the given class (or interface) name will not be accepted.
   */
  public $strict = false;
  
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('name', 'strict'));
    
    parent::initAnnotation($properties);
    
    if (!isset($this->name))
      throw new AnnotationException('ClassAnnotation requires a name property');
    
    if (!is_bool($this->strict))
      throw new AnnotationException('ClassAnnotation requires a boolean strict property');
  }
}

/**
 * Specifies validation against a fixed enumeration of valid choices.
 */
class EnumAnnotation extends Annotation implements IValidationAnnotation
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

/**
 * Specifies validation against a minimum and/or maximum numeric value.
 */
class RangeAnnotation extends Annotation implements IValidationAnnotation
{
  /**
   * @var mixed $min Minimum numeric value (integer or floating point)
   */
  public $min=null;
  
  /**
   * @var mixed $max Maximum numeric value (integer or floating point)
   */
  public $max=null;
  
  public function initAnnotation($properties)
  {
    if (isset($properties[0]))
    {
      if (isset($properties[1]))
      {
        $this->min = $properties[0];
        $this->max = $properties[1];
        unset($properties[1]);
      }
      else
        $this->max = $properties[0];
      
      unset($properties[0]);
    }
    
    parent::initAnnotation($properties);
    
    if ($this->min!==null && !is_int($this->min) && !is_float($this->min))
      throw new AnnotationException('RangeAnnotation requires a numeric (float or int) min property');
    if ($this->max!==null && !is_int($this->max) && !is_float($this->max))
      throw new AnnotationException('RangeAnnotation requires a numeric (float or int) max property');
    
    if ($this->min===null && $this->max===null)
      throw new AnnotationException('RangeAnnotation requires a min and/or max property');
  }
}

/**
 * Specifies validation of a string against a regular expression pattern.
 */
class PatternAnnotation extends Annotation implements IValidationAnnotation
{
  /**
   * @var string The regular expression pattern to validate against.
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
 
/**
 * Specifies validation requiring a non-empty value.
 */
class RequiredAnnotation extends Annotation implements IValidationAnnotation
{
}

/**
 * Specifies validation of a string, requiring a minimum and/or maximum length.
 */
class LengthAnnotation extends Annotation implements IValidationAnnotation
{
  /**
   * @var mixed Minimum string length (or null, if no minimum)
   */
  public $min=null;
  
  /**
   * @var mixed Maximum string length (or null, if no maximum)
   */
  public $max=null;
  
  public function initAnnotation($properties)
  {
    if (isset($properties[0]))
    {
      if (isset($properties[1]))
      {
        $this->min = $properties[0];
        $this->max = $properties[1];
        unset($properties[1]);
      }
      else
        $this->max = $properties[0];
      
      unset($properties[0]);
    }
    
    parent::initAnnotation($properties);
    
    if ($this->min!==null && !is_int($this->min))
      throw new AnnotationException('LengthAnnotation requires an (integer) min property');
    if ($this->max!==null && !is_int($this->max))
      throw new AnnotationException('LengthAnnotation requires an (integer) max property');
    
    if ($this->min===null && $this->max===null)
      throw new AnnotationException('LengthAnnotation requires a min and/or max property');
  }
}
