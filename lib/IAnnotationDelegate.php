<?php

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
