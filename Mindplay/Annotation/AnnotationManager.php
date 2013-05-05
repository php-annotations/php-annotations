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

namespace Mindplay\Annotation;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use Mindplay\Annotation\AnnotationParser;
use Mindplay\Annotation\UsageAnnotation;
use Mindplay\Annotation\AnnotationCache;

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
     * @var AnnotationCache|bool a cache-provider used to store annotation-data after parsing; or false to disable caching
     * @see getAnnotationData()
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
        'throws'         => false,
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
     * An internal cache for annotation-data loaded from source-code files
     *
     * @var array map where $member_name => annotation-data
     */
    protected $data = array();

    /**
     * @var array[] An internal cache for Annotation instances
     * @see getAnnotations()
     */
    protected $annotations = array();

    /**
     * @var bool[] An array of flags indicating which annotation sets have been initialized
     * @see getAnnotations()
     */
    protected $initialized = array();

    /**
     * @var array An internal cache for UsageAnnotation instances
     */
    protected $usage = array();

    /**
     * @var $_usageAnnotation UsageAnnotation The standard UsageAnnotation
     */
    private $_usageAnnotation;

    /**
     * @var string a seed for caching - used when generating cache keys, to prevent collisions
     * when using more than one AnnotationManager in the same application.
     */
    private $_cacheSeed = '';

    /**
     * Initialize the Annotation Manager
     *
     * @param string $cacheSeed only needed if using more than one AnnotationManager in the same application
     */
    public function __construct($cacheSeed = '')
    {
        $this->_cacheSeed = $cacheSeed;
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
        if (!isset($this->parser)) {
            $this->parser = new AnnotationParser($this);
            $this->parser->debug = $this->debug;
            $this->parser->autoload = $this->autoload;
        }

        return $this->parser;
    }

    /**
     * Retrieves annotation-data from a given source-code file.
     *
     * Member-names in the returned array have the following format: Class, Class::method or Class::$member
     *
     * @param string $path the path of the source-code file from which to obtain annotation-data.
     * @return array[] map where $member_name => array of annotation-data
     *
     * @throws AnnotationException if cache is not configured
     *
     * @see $data
     * @see $cache
     */
    protected function getAnnotationData($path)
    {
        if (!isset($this->data[$path])) {
            if ($this->cache === null) {
                throw new AnnotationException("AnnotationManager::\$cache is not configured");
            }

            if ($this->cache === false) {
                # caching is disabled
                $data = eval($this->getParser()->parseFile($path));
            } else {
                $key = basename($path) . '-' . sprintf('%x', crc32($path . $this->_cacheSeed));

                if (($this->cache->exists($key) === false) || (filemtime($path) > $this->cache->getTimestamp($key))) {
                    $code = $this->getParser()->parseFile($path);
                    $this->cache->store($key, $code);
                }

                $data = $this->cache->fetch($key);
            }

            $this->data[$path] = $data;
        }

        return $this->data[$path];
    }

    /**
     * Resolves a name, using built-in annotation name resolution rules, and the registry.
     *
     * @param string $name the annotation-name
     *
     * @return string|bool The fully qualified annotation class-name, or false if the
     * requested annotation has been disabled (set to false) in the registry.
     *
     * @see $registry
     */
    public function resolveName($name)
    {
        if (strpos($name, '\\') !== false) {
            return $name . $this->suffix; // annotation class-name is fully qualified
        }

        $type = lcfirst($name);

        if (isset($this->registry[$type])) {
            return $this->registry[$type]; // type-name is registered
        }

        $type = ucfirst(strtr($name, '-', '_')) . $this->suffix;

        return strlen($this->namespace)
            ? $this->namespace . '\\' . $type
            : $type;
    }

    /**
     * Constructs, initializes and returns IAnnotation objects
     *
     * @param string $class The name of the class from which to obtain Annotations
     * @param string $member The type of member, e.g. "class", "property" or "method"
     * @param string $name Optional member name, e.g. "method" or "$property"
     *
     * @return IAnnotation[] array of IAnnotation objects for the given class/member/name
     * @throws AnnotationException for bad annotations
     */
    protected function getAnnotations($class, $member = 'class', $name = null)
    {
        $key = $class . ($name ? '::' . $name : '');

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

            $reflection = new ReflectionClass($class);
            $specs = $this->getAnnotationData($reflection->getFileName());

            if (isset($specs[$key])) {
                $annotations = array();

                foreach ($specs[$key] as $spec) {
                    $type = $spec['#type'];
                    unset($spec['#type']);

                    if (!class_exists($type, $this->autoload)) {
                        throw new AnnotationException("annotation type {$type} not found");
                    }

                    $annotation = new $type;

                    if (!($annotation instanceof IAnnotation)) {
                        throw new AnnotationException("annotation type {$type} does not implement the mandatory IAnnotation interface");
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
     * @param array &$annotations An array of IAnnotation objects to be validated.
     * @param string $member The type of member to validate against (e.g. "class", "property" or "method")
     * @throws AnnotationException if a constraint is violated
     */
    protected function applyConstraints(array &$annotations, $member)
    {
        foreach ($annotations as $outer => $annotation) {
            $type = get_class($annotation);

            $usage = $this->getUsage($type);

            if (!$usage->$member) {
                throw new AnnotationException("{$type} cannot be applied to a {$member}");
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
                            throw new AnnotationException("only one annotation of type {$type} may be applied to the same {$member}");
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
     *
     * @return array The filtered array of annotation objects - may return an empty array
     */
    protected function filterAnnotations($annotations, $type)
    {
        if (substr($type, 0, 1) === '@') {
            $type = $this->resolveName(substr($type, 1));
        }

        if ($type === false) {
            return array();
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
     * @return UsageAnnotation
     * @throws AnnotationException if the given class-name is invalid; if the annotation-type has no defined usage
     */
    public function getUsage($class)
    {
        if ($class == 'Mindplay\\Annotation\\UsageAnnotation') {
            return $this->_usageAnnotation;
        }

        if (!isset($this->usage[$class])) {
            if (!class_exists($class, $this->autoload)) {
                throw new AnnotationException("undefined Annotation type '{$class}'");
            }

            $usage = $this->getAnnotations($class);

            if (count($usage) == 0) {
                if ($parent = get_parent_class($class)) {
                    $usage = $this->getUsage($parent);
                } else {
                    throw new AnnotationException("the class '{$class}' must have exactly one UsageAnnotation");
                }
            } else {
                if (count($usage) !== 1 || !($usage[0] instanceof UsageAnnotation)) {
                    throw new AnnotationException("the class '{$class}' must have exactly one UsageAnnotation (no other Annotations are allowed)");
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
     * @param string|ReflectionClass $class A class name, an object, or a ReflectionClass instance
     * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
     *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
     *
     * @return Annotation[] Annotation instances
     * @throws AnnotationException if a given class-name is undefined
     */
    public function getClassAnnotations($class, $type = null)
    {
        if ($class instanceof ReflectionClass) {
            $class = $class->getName();
        } else if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        if (!class_exists($class, $this->autoload)) {
            throw new AnnotationException("undefined class {$class}");
        }

        if ($type === null) {
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
     *
     * @return array Annotation instances
     */
    public function getMethodAnnotations($class, $method = null, $type = null)
    {
        if ($class instanceof ReflectionClass) {
            $class = $class->getName();
        } else if ($class instanceof ReflectionMethod) {
            $method = $class->name;
            $class = $class->class;
        } else if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        if (!class_exists($class, $this->autoload)) {
            throw new AnnotationException("undefined class {$class}");
        }

        if (!method_exists($class, $method)) {
            throw new AnnotationException("undefined method {$class}::{$method}()");
        }

        if ($type === null) {
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
     *
     * @return array Annotation instances
     *
     * @throws AnnotationException
     */
    public function getPropertyAnnotations($class, $property = null, $type = null)
    {
        if ($class instanceof ReflectionClass) {
            $class = $class->getName();
        } else if ($class instanceof ReflectionProperty) {
            $property = $class->name;
            $class = $class->class;
        } else if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        if (!class_exists($class, $this->autoload)) {
            throw new AnnotationException("undefined class {$class}");
        }

        if ($type === null) {
            return $this->getAnnotations($class, 'property', '$' . $property);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class, 'property', '$' . $property), $type);
        }
    }
}
