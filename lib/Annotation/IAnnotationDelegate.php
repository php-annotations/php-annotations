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

namespace Annotation;

/**
 * This interface enables an Annotation to delegate preceding Annotations to
 * another (virtual or "magic") member, e.g. another method or property.
 */
interface IAnnotationDelegate
{
  /**
   * @return string The member name to delegate to, e.g. "$property" or "methodName".
   */
  public function delegateAnnotation();
}
