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

use \Exception;

/**
 * This exception is thrown by various classes in the annotations package,
 * making it possible to catch annotation-specific exceptions in user code.
 */
class AnnotationException extends Exception
{
}
