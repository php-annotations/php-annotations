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
 * Specifies the name of a view to use to format a class or property for display.
 *
 * When rendering forms/widgets/inputs, if an EditorAnnotation is present, it
 * takes precence over a ViewAnnotation - otherwise, the ViewAnnotation may be
 * used to establish the name of a view to use for rendering an input, too.
 *
 * @usage('class'=>true, 'property'=>true, 'inherited'=>true)
 */
class ViewAnnotation extends Annotation
{
  /**
   * The name of the view to use when displaying a class or property.
   */
  public $name;
}
