<?php

/**
 * Specifies how to display or format a property value (for display-purposes).
 */
class FormatAnnotation extends Annotation
{
  /**
   * @var $format string A formatting string, compatible with sprintf()
   * @see http://php.net/sprintf
   */
  public $format;
  
  /**
   * @var $default string String to be used in place of an empty property value.
   */
  public $default;
  
  /**
   * @var $callback mixed Standard PHP callback array (class name|object, method name) or function name.
   * This callback will be invoked with $format as the first argument, and the property value as the second argument.
   */
  public $callback = 'sprintf';
}
