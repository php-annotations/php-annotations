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

namespace Mindplay\Annotation\Standard;

use Mindplay\Annotation\AnnotationException;
use Mindplay\Annotation\IAnnotationParser;
use Mindplay\Annotation\Annotation;

/**
 * Defines the return-type of a function or method
 *
 * @usage('method'=>true, 'inherited'=>true)
 */
class ReturnAnnotation extends Annotation implements IAnnotationParser
{
    /**
     * @var string
     */
    public $type;

    /**
     * Parse the standard PHP-DOC
     * @param string $value
     */
    public static function parseAnnotation($value)
    {
        $parts = explode(' ', trim($value), 2);

        return array('type' => array_shift($parts));
    }

    /**
     * Initialize the annotation.
     */
    public function initAnnotation($properties)
    {
        $this->map($properties, array('type'));

        parent::initAnnotation($properties);

        if (!isset($this->type)) {
            throw new AnnotationException('ReturnAnnotation requires a type property');
        }
    }
}
