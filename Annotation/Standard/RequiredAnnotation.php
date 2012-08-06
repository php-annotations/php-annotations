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

namespace Mindplay\Annotation\Standard;

/**
 * Specifies validation requiring a non-empty value.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class RequiredAnnotation extends ValidationAnnotationBase
{
  // @todo add flags indicating what "empty" means
}
