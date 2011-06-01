<?php

/**
 * Indicates whether a property should be user-editable or not.
 */
class EditableAnnotation extends Annotation implements IDataAnnotation
{
  /**
   * @var $allow boolean Indicates whether or not a property is editable.
   */
  public $allow=false;
  
  /**
   * @var $first boolean Inidates whether or not a property is editable on a new entity.
   */
  public $first=false;
}
