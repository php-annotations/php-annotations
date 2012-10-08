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
 * Abstact base class for validation annotations.
 */
abstract class ValidationAnnotationBase extends Annotation
{
  /**
   * @var string The error-message to display on validation failure.
   */
  public $message;
}
