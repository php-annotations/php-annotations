<?php

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
