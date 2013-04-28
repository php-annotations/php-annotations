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

use Mindplay\Annotation\Annotation;

/**
 * Specifies the name of a view to use for rendering an input (form element)
 * for a class or property.
 *
 * When rendering forms/widgets/inputs, if an EditorAnnotation is present, it
 * takes precence over a ViewAnnotation - otherwise, the ViewAnnotation may be
 * used to establish the name of a view to use for rendering an input, too.
 *
 * @usage('class'=>true, 'property'=>true, 'inherited'=>true)
 */
class EditorAnnotation extends Annotation
{
    /**
     * @var string The name of the view to use when editing a class or property.
     */
    public $name;

    /**
     * Initialize the annotation.
     */
    public function initAnnotation($properties)
    {
        $this->map($properties, array('name'));

        parent::initAnnotation($properties);
    }
}
