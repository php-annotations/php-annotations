<?php

### STUB CLASSES ONLY ###

/**
 * Interface for data Annotations
 */
interface IDataAnnotation
{
}

/**
 * Indicates whether a property should be user-editable or not.
 */
class EditAnnotation extends Annotation implements IDataAnnotation
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

class KeyAnnotation extends Annotation implements IDataAnnotation
{
  public $name;
}

class ColumnAnnotation extends Annotation implements IDataAnnotation
{
  public $name;
}

class AssociationAnnotation extends Annotation implements IDataAnnotation
{
}

class ConcurrencyCheckAnnotation extends Annotation implements IDataAnnotation
{
}

class TimestampAnnotation extends Annotation implements IDataAnnotation
{
}

class ReadOnlyAnnotation extends Annotation implements IDataAnnotation
{
}

/**
 * Indicates a property used to track changes, e.g. when/how/by whom an entity was created or changed.
 */
class ChangeAnnotation extends Annotation implements IDataAnnotation
{
  /**
   * @var $track string The type of change to track - suggested values include "created", "modified", "createdBy" and "modifiedBy"
   */
  public $track;
}
