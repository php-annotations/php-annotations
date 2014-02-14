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

use mindplay\annotations\AnnotationException;

/**
 * Specifies a custom validation callback method.
 */
class ValidateAnnotation extends ValidationAnnotationBase
{
    /**
     * @var mixed An object, a class-name, or a function name.
     */
    public $type;

    /**
     * @var string Optional, identifies a class/object method.
     */
    public $method = null;

    /**
     * Initialize the annotation.
     */
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type', 'method'));

        parent::initAnnotation($properties);

        if (!isset($this->type)) {
            throw new AnnotationException('type property not set');
        }
    }

    /**
     * @return mixed A standard PHP callback, e.g. an array($object, $method) pair, or a function name.
     */
    public function getCallback()
    {
        if ($this->method !== null) {
            return array($this->type, $this->method);
        } else {
            return $this->type;
        }
    }
}
