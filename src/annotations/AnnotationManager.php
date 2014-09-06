<?php

/**
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <https://github.com/mindplay-dk/php-annotations>
 */

namespace mindplay\annotations;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use mindplay\annotations\AnnotationParser;
use mindplay\annotations\UsageAnnotation;
use mindplay\annotations\AnnotationCache;

/**
 * This class manages the retrieval of Annotations from source code files
 */
class AnnotationManager
{
    const MEMBER_CLASS = 'class';

    const MEMBER_PROPERTY = 'property';

    const MEMBER_METHOD = 'method';

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
        'api'            => false,
        'abstract'       => false,
        'access'         => false,
        'author'         => false,
        'category'       => false,
        'copyright'      => false,
        'deprecated'     => false,
        'example'        => false,
        'filesource'     => false,
        'final'          => false,
        'global'         => false,
        'ignore'         => false,
        'internal'       => false,
        'license'        => false,
        'link'           => false,
        'method'         => 'mindplay\annotations\standard\MethodAnnotation',
        'name'           => false,
        'package'        => false,
        'param'          => 'mindplay\annotations\standard\ParamAnnotation',
        'property'       => 'mindplay\annotations\standard\PropertyAnnotation',
        'property-read'  => 'mindplay\annotations\standard\PropertyReadAnnotation',
        'property-write' => 'mindplay\annotations\standard\PropertyWriteAnnotation',
        'return'         => 'mindplay\annotations\standard\ReturnAnnotation',
        'see'            => false,
        'since'          => false,
        'source'         => false,
        'static'         => false,
        'staticvar'      => false,
        'subpackage'     => false,
        'todo'           => false,
        'tutorial'       => false,
        'throws'         => false,
        'type'           => 'mindplay\annotations\standard\TypeAnnotation',
        'usage'          => 'mindplay\annotations\UsageAnnotation',
        'uses'           => false,
        'var'            => 'mindplay\annotations\standard\VarAnnotation',
        'version'        => false,
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
     * @var AnnotationFile[] hash where absolute path to php source-file => AnnotationFile instance
     */
    protected $files = array();

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
     * @var UsageAnnotation[] An internal cache for UsageAnnotation instances
     */
    protected $usage = array();

    /**
     * @var UsageAnnotation The standard UsageAnnotation
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
     * Creates and returns the AnnotationParser instance
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
     * @return AnnotationFile
     *
     * @throws AnnotationException if cache is not configured
     *
     * @see $files
     * @see $cache
     */
    protected function getAnnotationFile($path)
    {
        if (!isset($this->files[$path])) {
            if ($this->cache === null) {
                throw new AnnotationException("AnnotationManager::\$cache is not configured");
            }

            if ($this->cache === false) {
                # caching is disabled
                $code = $this->getParser()->parseFile($path);
                $data = eval($code);
            } else {
                $key = basename($path) . '-' . sprintf('%x', crc32($path . $this->_cacheSeed));

                if (($this->cache->exists($key) === false) || (filemtime($path) > $this->cache->getTimestamp($key))) {
                    $code = $this->getParser()->parseFile($path);
                    $this->cache->store($key, $code);
                }

                $data = $this->cache->fetch($key);
            }

            $this->files[$path] = new AnnotationFile($path, $data);
        }

        return $this->files[$path];
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
     * @param string $class_name The name of the class from which to obtain Annotations
     * @param string $member_type The type of member, e.g. "class", "property" or "method"
     * @param string $member_name Optional member name, e.g. "method" or "$property"
     *
     * @return IAnnotation[] array of IAnnotation objects for the given class/member/name
     * @throws AnnotationException for bad annotations
     */
    protected function getAnnotations($class_name, $member_type = self::MEMBER_CLASS, $member_name = null)
    {
        $key = $class_name . ($member_name ? '::' . $member_name : '');

        if (!isset($this->initialized[$key])) {
            $annotations = array();

            if ($member_type !== self::MEMBER_CLASS) {
                $this->getAnnotations($class_name, self::MEMBER_CLASS);
            }

            if ($parent = get_parent_class($class_name)) {
                if ($parent !== __NAMESPACE__ . '\Annotation') {
                    foreach ($this->getAnnotations($parent, $member_type, $member_name) as $annotation) {
                        if ($this->getUsage(get_class($annotation))->inherited) {
                            $annotations[] = $annotation;
                        }
                    }
                }
            }

            $reflection = new ReflectionClass($class_name);

            if ($reflection->getFileName() && !$reflection->isInternal()) {
                $file = $this->getAnnotationFile($reflection->getFileName());
            }

            if (isset($file) && isset($file->data[$key])) {
                foreach ($file->data[$key] as $spec) {
                    $name = $spec['#name']; // currently unused
                    $type = $spec['#type'];

                    unset($spec['#name'], $spec['#type']);

                    if (!class_exists($type, $this->autoload)) {
                        throw new AnnotationException("annotation type {$type} not found");
                    }

                    $annotation = new $type;

                    if (!($annotation instanceof IAnnotation)) {
                        throw new AnnotationException("annotation type {$type} does not implement the mandatory IAnnotation interface");
                    }

                    if ($annotation instanceof IAnnotationFileAware) {
                        $annotation->setAnnotationFile($file);
                    }

                    $annotation->initAnnotation($spec);

                    $annotations[] = $annotation;
                }
            }

            $this->annotations[$key] = $this->applyConstraints($annotations, $member_type);

            $this->initialized[$key] = true;
        }

        return $this->annotations[$key];
    }

    /**
     * Validates the constraints (as defined by the UsageAnnotation of each annotation) of a
     * list of annotations for a given type of member.
     *
     * @param IAnnotation[] $annotations An array of IAnnotation objects to be validated (sorted with inherited annotations on top).
     * @param string        $member      The type of member to validate against (e.g. "class", "property" or "method").
     *
     * @return IAnnotation[] validated and filtered list of IAnnotations objects
     *
     * @throws AnnotationException if a constraint is violated.
     */
    protected function applyConstraints(array $annotations, $member)
    {
        $result = array();
        $annotationCount = count($annotations);

        foreach ($annotations as $outerIndex => $annotation) {
            $type = get_class($annotation);
            $usage = $this->getUsage($type);

            // Checks, that annotation can be applied to given class/method/property according to it's @usage annotation.
            if (!$usage->$member) {
                throw new AnnotationException("{$type} cannot be applied to a {$member}");
            }

            if (!$usage->multiple) {
                // Process annotation coming after current (in the outer loop) and of same type.
                for ($innerIndex = $outerIndex + 1; $innerIndex < $annotationCount; $innerIndex += 1) {
                    if (!$annotations[$innerIndex] instanceof $type) {
                        continue;
                    }

                    if ($usage->inherited) {
                        continue 2; // Another annotation (in inner loop) overrides this one (in outer loop) - skip it.
                    }

                    throw new AnnotationException("only one annotation of type {$type} may be applied to the same {$member}");
                }
            }

            $result[] = $annotation;
        }

        return $result;
    }

    /**
     * Filters annotations by class name
     *
     * @param IAnnotation[] $annotations An array of annotation objects
     * @param string $type The class-name by which to filter annotation objects; or annotation
     * type-name with a leading "@", e.g. "@var", which will be resolved through the registry
     *
     * @return array The filtered array of annotation objects - may return an empty array
     */
    protected function filterAnnotations(array $annotations, $type)
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
        if ($class === $this->registry['usage']) {
            return $this->_usageAnnotation;
        }

        if (!isset($this->usage[$class])) {
            if (!class_exists($class, $this->autoload)) {
                throw new AnnotationException("undefined Annotation type '{$class}'");
            }

            $usage = $this->getAnnotations($class);

            if (count($usage) === 0) {
                throw new AnnotationException("the class '{$class}' must have exactly one UsageAnnotation");
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
     * @param string|object|ReflectionClass $class A class name, an object, or a ReflectionClass instance
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
        } elseif (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        if (!class_exists($class, $this->autoload)) {
            $isTrait = function_exists('trait_exists') ? trait_exists($class, $this->autoload) : false;

            if (interface_exists($class, $this->autoload) || $isTrait) {
                throw new AnnotationException('Reading annotations from interface/trait "' . $class . '" is not supported');
            }

            throw new AnnotationException('Unable to read annotations from an undefined class "' . $class . '"');
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
     * @param string|object|ReflectionClass|ReflectionMethod $class A class name, an object, a ReflectionClass, or a ReflectionMethod instance
     * @param string $method The name of a method of the given class (or null, if the first parameter is a ReflectionMethod)
     * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
     *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
     *
     * @throws AnnotationException for undefined method or class-name
     * @return IAnnotation[] list of Annotation objects
     */
    public function getMethodAnnotations($class, $method = null, $type = null)
    {
        if ($class instanceof ReflectionClass) {
            $class = $class->getName();
        } elseif ($class instanceof ReflectionMethod) {
            $method = $class->name;
            $class = $class->class;
        } elseif (is_object($class)) {
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
            return $this->getAnnotations($class, self::MEMBER_METHOD, $method);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class, self::MEMBER_METHOD, $method), $type);
        }
    }

    /**
     * Inspects Annotations applied to a given property
     *
     * @param string|object|ReflectionClass|ReflectionProperty $class A class name, an object, a ReflectionClass, or a ReflectionProperty instance
     * @param string $property The name of a defined property of the given class (or null, if the first parameter is a ReflectionProperty)
     * @param string $type An optional annotation class/interface name - if specified, only annotations of the given type are returned.
     *                     Alternatively, prefixing with "@" invokes name-resolution (allowing you to query by annotation name.)
     *
     * @return IAnnotation[] list of Annotation objects
     *
     * @throws AnnotationException for undefined class-name
     */
    public function getPropertyAnnotations($class, $property = null, $type = null)
    {
        if ($class instanceof ReflectionClass) {
            $class = $class->getName();
        } elseif ($class instanceof ReflectionProperty) {
            $property = $class->name;
            $class = $class->class;
        } elseif (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        if (!class_exists($class, $this->autoload)) {
            throw new AnnotationException("undefined class {$class}");
        }

        if (!property_exists($class, $property)) {
            throw new AnnotationException("undefined property {$class}::\${$property}");
        }

        if ($type === null) {
            return $this->getAnnotations($class, self::MEMBER_PROPERTY, '$' . $property);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class, self::MEMBER_PROPERTY, '$' . $property), $type);
        }
    }
}
