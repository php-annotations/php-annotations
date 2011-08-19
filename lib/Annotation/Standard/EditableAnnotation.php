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

namespace Annotation\Lib;

use Annotation\Annotation;

/**
 * Indicates whether a property should be user-editable or not.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class EditableAnnotation extends Annotation
{
  /**
   * @var bool Indicates whether or not a property is editable.
   */
  public $allow=false;

  /**
   * @var bool Indicates whether or not a property is editable on a new instance.
   *           (this value only has meaning when $allow is false.)
   */
  public $first=false;

  /**
   * Initialize the annotation.
   */
  public function initAnnotation($properties)
  {
    $this->_map($properties, array('allow', 'first'));
    
    parent::initAnnotation($properties);
  }
}
