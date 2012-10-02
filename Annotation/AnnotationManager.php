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

namespace Mindplay\Annotation;

use \Mindplay\Annotation\Cache\AnnotationCacheNotConfiguredException;
use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;

/**
 * This class manages the retrieval of Annotations from source code files
 */
class AnnotationManager
{
  /**
   * @var boolean Enable PHP autoloader when searching for annotation classes (defaults to true)
   */
  public $autoload = true;
   
  /**
   * @var string The class-name suffix for Annotation classes.
   */
  public $suffix = 'Annotation';
  
  /**
   * @var string The default namespace for annotations with no namespace qualifier.
   */
  public $namespace = '';
  
  /**
   * @var \Mindplay\Annotation\Cache\IAnnotationCache
   */
  public $cache;
  
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
    'display'        => 'Mindplay\Annotation\Standard\DisplayAnnotation',
    'editable'       => 'Mindplay\Annotation\Standard\EditableAnnotation',
    'editor'         => 'Mindplay\Annotation\Standard\EditorAnnotation',
    'enum'           => 'Mindplay\Annotation\Standard\EnumAnnotation',
    'example'        => false,
    'filesource'     => false,
    'final'          => false,
    'format'         => 'Mindplay\Annotation\Standard\FormatAnnotation',
    'global'         => false,
    'ignore'         => false,
    'internal'       => false,
    'length'         => 'Mindplay\Annotation\Standard\LengthAnnotation',
    'license'        => false,
    'link'           => false,
    'match'          => 'Mindplay\Annotation\Standard\MatchAnnotation',
    'method'         => 'Mindplay\Annotation\Standard\MethodAnnotation',
    'name'           => false,
    'package'        => false,
    'param'          => 'Mindplay\Annotation\Standard\ParamAnnotation',
    'property'       => 'Mindplay\Annotation\Standard\PropertyAnnotation',
    'property-read'  => 'Mindplay\Annotation\Standard\PropertyReadAnnotation',
    'property-write' => 'Mindplay\Annotation\Standard\PropertyWriteAnnotation',
    'range'          => 'Mindplay\Annotation\Standard\RangeAnnotation',
    'required'       => 'Mindplay\Annotation\Standard\RequiredAnnotation',
    'return'         => 'Mindplay\Annotation\Standard\ReturnAnnotation',
    'see'            => false,
    'since'          => false,
    'static'         => false,
    'staticvar'      => false,
    'subpackage'     => false,
    'text'           => 'Mindplay\Annotation\Standard\TextAnnotation',
    'todo'           => false,
    'tutorial'       => false,
    'throws' => false,
    'usage'          => 'Mindplay\Annotation\UsageAnnotation',
    'uses'           => false,
    'validate'       => 'Mindplay\Annotation\Standard\ValidateAnnotation',
    'var'            => 'Mindplay\Annotation\Standard\VarAnnotation',
    'view'           => 'Mindplay\Annotation\Standard\ViewAnnotation',
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
   * @var $_usageAnnotation UsageAnnotation The standard UsageAnnotation
   */
  protected $_usageAnnotation;
  
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
      $this->parser = new AnnotationParser($this);
      $this->parser->debug = $this->debug;
      $this->parser->autoload = $this->autoload;
    }
    return $this->parser;
  }
  
  /**
   * Retrieves all Annotation specifications for a given source code file.
   *
   * @param string $class The class to retrieve the annotations
   * @return array Specifications for Annotations (arrays keyed by Class, Class::method or Class::$member)
   */
  protected function getClassMetadata($class)
  {
    $reflection = new ReflectionClass($class);
    $path = $reflection->getFileName();
    
    if (!isset($this->specs[$path]))
    {
      try {
        $this->specs[$path] = $this->getFromCache($reflection, $path);
      } catch (AnnotationCacheNotConfiguredException $e) {
        trigger_error($e->getMessage(), E_USER_NOTICE);
        $this->specs[$path] = eval($this->getParser()->parseFile($path));
      }
    }
    
    return $this->specs[$path];
  }

  /**
   * @param ReflectionClass $reflection
   * @param string $filePath
   * @return mixed
   */
  protected function getFromCache(ReflectionClass $reflection, $filePath)
  {
    if (is_null($this->cache)) {
      throw new AnnotationCacheNotConfiguredException(
        __METHOD__ . " : AnnotationManager::\$cache is not configured"
      );
    }

    $cacheId = $this->cache->createId($reflection);

    if (!$this->cache->exists($cacheId) || filemtime($filePath) > $this->cache->getLastChangeTime($cacheId)) {
      $this->cache->store($cacheId, $this->getParser()->parseFile($filePath));
    }

    return $this->cache->get($cacheId);
  }
  
  /**
   * Resolves a name, using built-in annotation name resolution rules, and the registry.
   *
   * @return string|bool The fully qualified annotation class-name, or false if the
   * requested annotation has been disabled (set to false) in the registry.
   */
  public function resolveName($name)
  {
    if (strpos($name, '\\') !== false) {
      return $name.$this->suffix; // annotation class-name is fully qualified
    }
    
    $type = lcfirst($name);
    
    if (@$this->registry[$type] === false) {
      return false; // annotation is disabled
    }
    
    if (isset($this->registry[$type])) {
      return $this->registry[$type]; // type-name is registered
    }
    else {
      $type = ucfirst(strtr($name, '-', '_')).$this->suffix;
      
      return strlen($this->namespace) ? $this->namespace . '\\' . $type : $type;
    }
    
    return $type;
  }
  
  /**
   * Constructs, initializes and returns Annotation objects
   *
   * @param string $class The name of the class from which to obtain Annotations
   * @param string $member The type of member, e.g. "class", "property" or "method"
   * @param string $name Optional member name, e.g. "method" or "$property"
   */
  protected function getAnnotations($class, $member='class', $name=null)
  {
    $key = $class . ($name ? '::'.$name : '');
    
    if (!isset($this->initialized[$key])) {
      if (!isset($this->annotations[$key])) {
        $this->annotations[$key] = array();
      }
      
      if ($member !== 'class') {
        $this->getAnnotations($class, 'class');
      }
      
      if ($parent = get_parent_class($class)) {
        if ($parent !== 'Annotation\Annotation') {
          foreach ($this->getAnnotations($parent, $member, $name) as $annotation) {
            if ($this->getUsage(get_class($annotation))->inherited) {
              $this->annotations[$key][] = $annotation;
            }
          }
        }
      }
      
      $this->initialized[$key] = true;
      
      $specs = $this->getClassMetadata($class);
      
      if (isset($specs[$key])) {
        $annotations = array();
        
        foreach ($specs[$key] as $spec) {
          $type = array_shift($spec);
          
          if (!class_exists($type, $this->autoload)) {
            throw new AnnotationException(__CLASS__."::getAnnotations() : annotation type {$type} not found");
          }
          
          $annotation = new $type;

          if (!($annotation instanceof IAnnotation)) {
            throw new AnnotationException(__CLASS__."::getAnnotations() : annotation type {$type} does not implement the mandatory IAnnotation interface");
          }
          
          $annotation->initAnnotation($spec);
          
          $annotations[] = $annotation;
        }
        
        /*
        
        // This feature has been disabled in the 1.x branch of this library
        
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
        
        */
        
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
  protected function applyConstraints(array &$annotations, $member)
  {
    foreach ($annotations as $outer=>$annotation) {
      $type = get_class($annotation);
      
      $usage = $this->getUsage($type);
      
      if (!$usage->$member) {
        throw new AnnotationException(__CLASS__."::getAnnotations() : {$type} cannot be applied to a {$member}");
      }
      
      if (!$usage->multiple) {
        foreach ($annotations as $inner => $other) {
          if ($inner >= $outer) {
            break;
          }
          
          if ($other instanceof $type) {
            if ($usage->inherited) {
              unset($annotations[$inner]);
            } else {
              throw new AnnotationException(__CLASS__."::getAnnotations() : only one {$type} may be applied to the same {$member}");
            }
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
  protected function filterAnnotations($annotations, $type)
  {
    if (substr($type,0,1) === '@') {
      $type = $this->resolveName(substr($type,1));
    }
    
    $result = array();
    
    foreach ($annotations as $annotation) {
      if ($annotation instanceof $type) {
        $result[] = $annotation;
      }
    }
    
    return $result;
  }
  
  /**
   * Obtain the UsageAnnotation for a given Annotation class
   *
   * @param string $class The Annotation type class-name
   */
  public function getUsage($class)
  {
    if ($class=='Mindplay\\Annotation\\UsageAnnotation') {
      return $this->_usageAnnotation;
    }
    
    if (!isset($this->usage[$class])) {
      if (!class_exists($class, $this->autoload)) {
        throw new AnnotationException(__CLASS__."::getUsage() : undefined Annotation type '{$class}'");
      }
      
      $usage = $this->getAnnotations($class);
      
      if (count($usage)==0) {
        if ($parent = get_parent_class($class)) {
          $usage = $this->getUsage($parent);
        } else {
          throw new AnnotationException(__CLASS__."::getUsage() : the class '{$class}' must have exactly one UsageAnnotation");
        }
      } else {
        if (count($usage)!==1 || !($usage[0] instanceof UsageAnnotation)) {
          throw new AnnotationException(__CLASS__."::getUsage() : the class '{$class}' must have exactly one UsageAnnotation (no other Annotations are allowed)");
        } else {
          $usage = $usage[0];
        }
      }
      
      $this->usage[$class] = $usage;
    }
    
    return $this->usage[$class];
  }
  
  /**
   * Inspects Annotations applied to a given class
   *
   * @param mixed $class A class name, an object, or a ReflectionClass instance
   * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
   *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
   * @return array Annotation instances
   */
  public function getClassAnnotations($class, $type=null)
  {
    if ($class instanceof ReflectionClass) {
      $class = $class->getName();
    } else if (is_object($class)) {
      $class = get_class($class);
    }
    
    if (!class_exists($class, $this->autoload)) {
      throw new AnnotationException(__CLASS__."::getClassAnnotations() : undefined class {$class}");
    }
    
    if ($type===null) {
      return $this->getAnnotations($class);
    } else {
      return $this->filterAnnotations($this->getAnnotations($class), $type);
    }
  }
  
  /**
   * Inspects Annotations applied to a given method
   *
   * @param mixed $class A class name, an object, a ReflectionClass, or a ReflectionMethod instance
   * @param string $method The name of a method of the given class (or null, if the first parameter is a ReflectionMethod)
   * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
   *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
   * @return array Annotation instances
   */
  public function getMethodAnnotations($class, $method=null, $type=null)
  {
    if ($class instanceof ReflectionClass) {
      $class = $class->getName();
    } else if ($class instanceof ReflectionMethod) {
      $method = $class->name;
      $class = $class->class;
    } else if (is_object($class)) {
      $class = get_class($class);
    }
    
    if (!class_exists($class, $this->autoload)) {
      throw new AnnotationException(__CLASS__."::getMethodAnnotations() : undefined class {$class}");
    }
    
    if (!method_exists($class, $method)) {
      throw new AnnotationException(__CLASS__."::getMethodAnnotations() : undefined method {$class}::{$method}()");
    }
    
    if ($type===null) {
      return $this->getAnnotations($class, 'method', $method);
    } else {
      return $this->filterAnnotations($this->getAnnotations($class, 'method', $method), $type);
    }
  }
  
  /**
   * Inspects Annotations applied to a given property
   *
   * @param mixed $class A class name, an object, a ReflectionClass, or a ReflectionProperty instance
   * @param string $property The name of a defined property of the given class (or null, if the first parameter is a ReflectionProperty)
   * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
   *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
   * @return array Annotation instances
   */
  public function getPropertyAnnotations($class, $property=null, $type=null)
  {
    if ($class instanceof ReflectionClass) {
      $class = $class->getName();
    } else if ($class instanceof ReflectionProperty) {
      $property = $class->name;
      $class = $class->class;
    } else if (is_object($class)) {
      $class = get_class($class);
    }
    
    if (!class_exists($class, $this->autoload)) {
      throw new AnnotationException(__CLASS__."::getPropertyAnnotations() : undefined class {$class}");
    }
    
    if ($type===null) {
      return $this->getAnnotations($class, 'property', '$'.$property);
    } else {
      return $this->filterAnnotations($this->getAnnotations($class, 'property', '$'.$property), $type);
    }
  }
}
