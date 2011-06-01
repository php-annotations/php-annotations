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
 * Defines various display-related metadata.
 */
class DisplayAnnotation extends Annotation
{
  /**
   * @var $group string A group name - for use with helpers that render multiple fields as a group.
   */
  public $group;
  
  /**
   * @var $order integer Order index - for use with helpers that render multiple fields. Fields are sorted in ascending order.
   */
  public $order;
}
