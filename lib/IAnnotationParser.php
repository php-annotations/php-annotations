<?php

/**
 * This interface enables an Annotation to support PHP-DOC style Annotation
 * syntax - because this syntax is informal and varies between tags, such an
 * Annotation must be parsed by the individual Annotation class.
 */
interface IAnnotationParser
{
  /**
   * @param string $value The raw string value of the Annotation.
   * @return array An array of Annotation properties.
   */
  static function parseAnnotation($value);
}
