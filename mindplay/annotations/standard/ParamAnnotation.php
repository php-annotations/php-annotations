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

use mindplay\annotations\AnnotationContext;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\IAnnotationContext;
use mindplay\annotations\IAnnotationParser;
use mindplay\annotations\Annotation;

/**
 * Defines a method-parameter's type
 *
 * @usage('method'=>true, 'inherited'=>true, 'multiple'=>true)
 */
class ParamAnnotation extends Annotation implements IAnnotationParser, IAnnotationContext
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
         * Annotation file.
         *
         * @var AnnotationContext
         */
        protected $context;

    /**
     * Parse the standard PHP-DOC "param" annotation.
     *
     * @param string $value
     * @return array ['type', 'name']
     */
    public static function parseAnnotation($value)
    {
        $parts = explode(' ', trim($value), 3);

        return array('type' => $parts[0], 'name' => substr($parts[1], 1));
    }

    /**
     * Initialize the annotation.
     */
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type', 'name'));

        parent::initAnnotation($properties);

        if (!isset($this->type)) {
            throw new AnnotationException('ParamAnnotation requires a type property');
        }

        if (!isset($this->name)) {
            throw new AnnotationException('ParamAnnotation requires a name property');
        }

        $this->type = $this->context->resolveType($this->type);
    }

    public function setAnnotationContext(AnnotationContext $context)
    {
        $this->context = $context;
    }
}
