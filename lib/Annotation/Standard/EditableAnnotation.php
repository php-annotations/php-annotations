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

/**
 * Indicates whether a property should be user-editable or not.
 */
class EditableAnnotation extends Annotation implements IDataAnnotation
{
  /**
   * @var $allow boolean Indicates whether or not a property is editable.
   */
  public $allow=false;
  
  /**
   * @var $first boolean Inidates whether or not a property is editable on a new entity.
   */
  public $first=false;
}
