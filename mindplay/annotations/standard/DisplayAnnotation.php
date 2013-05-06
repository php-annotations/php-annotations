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

/**
 * Defines display-related metadata.
 *
 * @usage('class'=>true, 'property'=>true, 'inherited'=>true)
 */
class DisplayAnnotation extends Annotation
{
    /**
     * @var string A group name - for use with helpers that render multiple fields as a group.
     */
    public $group;

    /**
     * @var integer Order index - for use with helpers that render multiple fields. Fields are sorted in ascending order.
     */
    public $order;

    /**
     * Initialize the annotation.
     */
    public function initAnnotation($properties)
    {
        $this->map($properties, array('order'));

        parent::initAnnotation($properties);
    }
}
