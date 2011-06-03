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

namespace Annotation;

use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;

/**
 * This class manages the retrieval of Annotations from source code files
 */
class AnnotationManager
{
  /**
   * @var int The file mode used when creating cache files
   */
  public $fileMode = 0777;
  
  /**
   * @var boolean Enable PHP autoloader when searching for annotation classes (defaults to true)
   */
  public $autoload = true;
  
  /**
   * @var string Absolute path to a folder where cache files may be saved
   */
  public $cachePath = null;
  
  /**
   * @var string The class-name suffix for Annotation classes.
   */
  public $suffix = 'Annotation';
  
  /**
   * @var string The default namespace for annotations with no namespace qualifier.
   */
  public $namespace = '';
  
  /**
   * @var array List of registered annotation aliases.
   */
  public $registry = array(
    'abstract'       => false,
    'access'         => false,
    'author'         => false,
    'category'       => false,
    'copyright'      => false,
    'deprecated'     => false,
    'display'        => 'Annotation\\Standard\\DisplayAnnotation',
    'editable'       => 'Annotation\\Standard\\EditableAnnotation',
    'editor'         => 'Annotation\\Standard\\EditorAnnotation',
    'enum'           => 'Annotation\\Standard\\EnumAnnotation',
    'example'        => false,
    'filesource'     => false,
    'final'          => false,
    'format'         => 'Annotation\\Standard\\FormatAnnotation',
    'global'         => false,
    'ignore'         => false,
    'internal'       => false,
    'length'         => 'Annotation\\Standard\\LengthAnnotation',
    'license'        => false,
    'link'           => false,
    'match'          => 'Annotation\\Standard\\MatchAnnotation',
    'method'         => 'Annotation\\Standard\\MethodAnnotation',
    'name'           => false,
    'package'        => false,
    'param'          => 'Annotation\\Standard\\ParamAnnotation',
    'property'       => 'Annotation\\Standard\\PropertyAnnotation',
    'property-read'  => 'Annotation\\Standard\\PropertyReadAnnotation',
    'property-write' => 'Annotation\\Standard\\PropertyWriteAnnotation',
    'range'          => 'Annotation\\Standard\\RangeAnnotation',
    'required'       => 'Annotation\\Standard\\RequiredAnnotation',
    'return'         => 'Annotation\\Standard\\ReturnAnnotation',
    'see'            => false,
    'since'          => false,
    'static'         => false,
    'staticvar'      => false,
    'subpackage'     => false,
    'text'           => 'Annotation\\Standard\\TextAnnotation',
    'todo'           => false,
    'tutorial'       => false,
    'usage'          => 'Annotation\\UsageAnnotation',
    'uses'           => false,
    'validate'       => 'Annotation\\Standard\\ValidateAnnotation',
    'var'            => 'Annotation\\Standard\\VarAnnotation',
    'view'           => 'Annotation\\Standard\\ViewAnnotation',
  );
  
  /**
   * @var boolean $debug Set to TRUE to enable HTML output for debugging
   */
  public $debug = false;
  
  /**
   * @var AnnotationParser
   */
  protected $parser;
  
  /**
   * @var array An internal cache for specifications for loaded file Annotations
   */
  protected $specs = array();
  
  /**
   * @var array An internal cache for Annotation instances
   */
  protected $annotations = array();
  
  /**
   * @var array An array of flags indicating which annotation sets have been initialized
   */
  protected $initialized = array();
  
  /**
   * @var array An internal cache for UsageAnnotation instances
   */
  protected $usage = array();
  
  /**
   * @var string The PHP opening tag (used when writing cache files)
   */
  const PHP_TAG = "<?php\n\n";
  
  /**
   * @var $_usageAnnotation UsageAnnotation The standard UsageAnnotation
   */
  private $_usageAnnotation;
  
  /**
   * Initialize the Annotation Manager
   */
  public function __construct()
  {
    $this->_usageAnnotation = new UsageAnnotation();
    $this->_usageAnnotation->class = true;
    $this->_usageAnnotation->inherited = true;
  }
  
  /**
   * @internal Creates and returns the AnnotationParser instance
   * @return AnnotationParser
   */
  public function getParser()
  {
    if (!isset($this->parser))
    {
      $this->parser = new AnnotationParser;
      $this->parser->debug = $this->debug;
      $this->parser->namespace = $this->namespace;
      $this->parser->suffix = $this->suffix;
      $this->parser->registry = & $this->registry;
    }
    return $this->parser;
  }
  
  /**
   * @param string $path The full path to the source code file for which to calculate a cache path
   * @return string The path to the annotation cache file for the given path
   */
  protected function getAnnotationCache($path)
  {
    return $this->cachePath.DIRECTORY_SEPARATOR.basename($path).'-'.sprintf('%x',crc32($path)).'.annotations.php';
  }
  
  /**
   * Retrieves all Annotation specifications for a given source code file.
   *
   * @param string $path The full path to the source code file from which to retrieve Annotations
   * @return array Specifications for Annotations (arrays keyed by Class, Class::method or Class::$member)
   */
  private function getFileSpecs($path)
  {
    if (!isset($this->specs[$path]))
    {
      if (isset($this->specs[$path]))
        return $this->specs[$path];
      
      if ($this->cachePath!==null)
      {
        $file = $this->getAnnotationCache($path);
        
        if (!file_exists($file) || filemtime($path)>filemtime($file))
        {
          $code = self::PHP_TAG.$this->getParser()->parseFile($path);
          if (@file_put_contents($file, $code, LOCK_EX)==false || @chmod($file, $this->fileMode)==false)
            throw new AnnotationException(__CLASS__.'::getFileSpecs() : error writing cache file '.$file);
        }
        
        $this->specs[$path] = include($file);
      }
      else
      {
        trigger_error(__CLASS__."::getFileSpecs() : AnnotationManager::\$cachePath is not configured", E_USER_NOTICE);
        $this->specs[$path] = eval($this->getParser()->parseFile($path));
      }
    }
    
    return $this->specs[$path];
  }
  
  /**
   * Constructs, initializes and returns Annotation objects
   *
   * @param string $class The name of the class from which to obtain Annotations
   * @param string $member The type of member, e.g. "class", "property" or "method"
   * @param string $name Optional member name, e.g. "method" or "$property"
   */
  private function getAnnotations($class, $member='class', $name=null)
  {
    $key = $class . ($name ? '::'.$name : '');
    
    if (!isset($this->initialized[$key]))
    {
      if (!isset($this->annotations[$key]))
        $this->annotations[$key] = array();
      
      if ($member !== 'class')
      {
        $this->getAnnotations($class, 'class');
      }
      
      if ($parent = get_parent_class($class))
        if ($parent !== 'Annotation\\Annotation')
          foreach ($this->getAnnotations($parent, $member, $name) as $annotation)
            if ($this->getUsage(get_class($annotation))->inherited)
              $this->annotations[$key][] = $annotation;
      
      $this->initialized[$key] = true;
      
      $reflection = new ReflectionClass($class);
      $path = $reflection->getFileName();
      $specs = $this->getFileSpecs($path);
      
      if (isset($specs[$key]))
      {
        $annotations = array();
        
        foreach ($specs[$key] as $spec)
        {
          $type = array_shift($spec);
          
          if (!class_exists($type, $this->autoload))
            throw new AnnotationException(__CLASS__."::getAnnotations() : annotation type {$type} not found");
          
          $annotation = new $type;
          if (!($annotation instanceof IAnnotation))
            throw new AnnotationException(__CLASS__."::getAnnotations() : annotation type {$type} does not implement the mandatory IAnnotation interface");
          
          $annotation->initAnnotation($spec);
          
          $annotations[] = $annotation;
        }
        
        if ($member === 'class')
        {
          $offset = 0;
          
          foreach ($annotations as $index => $annotation)
          {
            if ($annotation instanceof IAnnotationDelegate)
            {
              $delegate = $class.'::'.$annotation->delegateAnnotation();
              
              for ($i=$offset; $i<$index; $i++)
              {
                $this->annotations[$delegate][] = $annotations[$i];
                unset($annotations[$i]);
              }
              
              $offset = $index+1;
            }
          }
        }
        
        $this->annotations[$key] = array_merge(
          $this->annotations[$key],
          $annotations
        );
      }
      
      $this->applyConstraints($this->annotations[$key], $member);
    }
    
    return $this->annotations[$key];
  }
  
  /**
   * Validates the constraints (as defined by the UsageAnnotation of each annotation) of a
   * list of annotations for a given type of member.
   *
   * @param array An array of IAnnotation objects to be validated.
   * @param string The type of member to validate against (e.g. "class", "property" or "method")
   */
  private function applyConstraints(&$annotations, $member)
  {
    foreach ($annotations as $outer=>$annotation)
    {
      $type = get_class($annotation);
      
      $usage = $this->getUsage($type);
      
      if (!$usage->$member)
        throw new AnnotationException(__CLASS__."::getAnnotations() : {$type} cannot be applied to a {$member}");
      
      if (!$usage->multiple)
      {
        foreach ($annotations as $inner=>$other)
        {
          if ($inner >= $outer)
            break;
          
          if ($other instanceof $type)
          {
            if ($usage->inherited)
              unset($annotations[$inner]);
            else
              throw new AnnotationException(__CLASS__."::getAnnotations() : only one {$type} may be applied to the same {$member}");
          }
        }
      }
    }
  }
  
  /**
   * Filters annotations by class name
   *
   * @param array $annotations An array of annotation objects
   * @param string $type The class name by which to filter annotation objects
   * @return array The filtered array of annotation objects - may return an empty array
   */
  private function filterAnnotations($annotations, $type)
  {
    $result = array();
    
    foreach ($annotations as $annotation)
      if ($annotation instanceof $type)
        $result[] = $annotation;
    
    return $result;
  }
  
  /**
   * Obtain the UsageAnnotation for a given Annotation class
   *
   * @param string $class The Annotation type class-name
   */
  public function getUsage($class)
  {
    if ($class=='Annotation\\UsageAnnotation')
      return $this->_usageAnnotation;
    
    if (!isset($this->usage[$class]))
    {
      if (!class_exists($class, $this->autoload))
        throw new AnnotationException(__CLASS__."::getUsage() : undefined Annotation type '{$class}'");
      
      $usage = $this->getAnnotations($class);
      
      if (count($usage)==0)
      {
        if ($parent = get_parent_class($class))
          $usage = $this->getUsage($parent);
        else
          throw new AnnotationException(__CLASS__."::getUsage() : the class '{$class}' must have exactly one UsageAnnotation");
      }
      else
      {
        if (count($usage)!==1 || !($usage[0] instanceof UsageAnnotation))
          throw new AnnotationException(__CLASS__."::getUsage() : the class '{$class}' must have exactly one UsageAnnotation (no other Annotations are allowed)");
        else
          $usage = $usage[0];
      }
      
      $this->usage[$class] = $usage;
    }
    
    return $this->usage[$class];
  }
  
  /**
   * Inspects Annotations applied to a given class
   *
   * @param mixed $class A class name, an object, or a ReflectionClass instance
   * @param string $type An optional annotation class name - if specified, only annotations of the given class are returned
   * @return array Annotation instances
   */
  public function getClassAnnotations($class, $type=null)
  {
    if (is_object($class))
      $class = get_class($class);
    else if ($class instanceof ReflectionClass)
      $class = $class->getName();
    
    if (!class_exists($class, $this->autoload))
      throw new AnnotationException(__CLASS__."::getClassAnnotations() : undefined class {$class}");
    
    if ($type===null)
      return $this->getAnnotations($class);
    else
      return $this->filterAnnotations($this->getAnnotations($class), $type);
  }
  
  /**
   * Inspects Annotations applied to a given method
   *
   * @param mixed $class A class name, an object, a ReflectionClass, or a ReflectionMethod instance
   * @param string $method The name of a method of the given class (or null, if the first parameter is a ReflectionMethod)
   * @param string $type An optional annotation class name - if specified, only annotations of the given class are returned
   * @return array Annotation instances
   */
  public function getMethodAnnotations($class, $method=null, $type=null)
  {
    if (is_object($class))
      $class = get_class($class);
    else if ($class instanceof ReflectionClass)
      $class = $class->getName();
    else if ($class instanceof ReflectionMethod)
    {
      $method = $class->name;
      $class = $class->class;
    }
    
    if (!class_exists($class, $this->autoload))
      throw new AnnotationException(__CLASS__."::getMethodAnnotations() : undefined class {$class}");
    
    if (!method_exists($class, $method))
      throw new AnnotationException(__CLASS__."::getMethodAnnotations() : undefined method {$class}::{$method}()");
    
    if ($type===null)
      return $this->getAnnotations($class, 'method', $method);
    else
      return $this->filterAnnotations($this->getAnnotations($class, 'method', $method), $type);
  }
  
  /**
   * Inspects Annotations applied to a given property
   *
   * @param mixed $class A class name, an object, a ReflectionClass, or a ReflectionProperty instance
   * @param string $method The name of a defined property of the given class (or null, if the first parameter is a ReflectionProperty)
   * @param string $type An optional annotation class name - if specified, only annotations of the given class are returned
   * @return array Annotation instances
   */
  public function getPropertyAnnotations($class, $property=null, $type=null)
  {
    if (is_object($class))
      $class = get_class($class);
    else if ($class instanceof ReflectionClass)
      $class = $class->getName();
    else if ($class instanceof ReflectionProperty)
    {
      $property = $class->name;
      $class = $class->class;
    }
    
    if (!class_exists($class, $this->autoload))
      throw new AnnotationException(__CLASS__."::getPropertyAnnotations() : undefined class {$class}");
    
    if ($type===null)
      return $this->getAnnotations($class, 'property', '$'.$property);
    else
      return $this->filterAnnotations($this->getAnnotations($class, 'property', '$'.$property), $type);
  }
}
