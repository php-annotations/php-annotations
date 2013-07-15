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

namespace mindplay\annotations\standard;

use mindplay\annotations\Annotation;
use mindplay\annotations\AnnotationContext;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\IAnnotationContext;
use mindplay\annotations\IAnnotationParser;

/**
 * Specifies the required data-type of a property.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class VarAnnotation extends Annotation implements IAnnotationParser, IAnnotationContext
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
     * Annotation file.
     *
     * @var AnnotationContext
     */
    protected $context;

    /**
     * Parse the standard PHP-DOC annotation
     * @param string $value
     * @return array
     */
    public static function parseAnnotation($value)
    {
        $parts = explode(' ', trim($value), 2);

        return array('type' => array_shift($parts));
    }

    /**
     * Initialize the annotation.
     */
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type'));

        parent::initAnnotation($properties);

        if (!isset($this->type)) {
            throw new AnnotationException('VarAnnotation requires a type property');
        }

        $this->type = $this->context->resolveType($this->type);
    }

    public function setAnnotationContext(AnnotationContext $context)
    {
        $this->context = $context;
    }
}
