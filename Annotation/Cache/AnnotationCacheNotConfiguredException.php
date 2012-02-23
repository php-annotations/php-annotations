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

namespace Mindplay\Annotation\Cache;

use \Mindplay\Annotation\AnnotationException;

/**
 * This exception is throwed when there's no cache storage configured
 *
 * @author Luís Otávio Cobucci Oblonczyk
 */
class AnnotationCacheNotConfiguredException extends AnnotationException
{
}