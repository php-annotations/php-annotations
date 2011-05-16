<?php

/**
 * Defines various display-related metadata.
 */
class DisplayAnnotation extends Annotation
{
  /**
   * @var $group string A group name - for use with helpers that render multiple fields as a group.
   */
  public $group;
  
  /**
   * @var $order integer Order index - for use with helpers that render multiple fields. Fields are sorted in ascending order.
   */
  public $order;
}
