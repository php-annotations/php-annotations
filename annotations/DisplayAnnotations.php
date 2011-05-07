<?php

### STUB CLASSES ONLY ###

/**
 * Interface for data validation Annotations
 */
interface IDisplayAnnotation
{
}

/**
 * Defines various text (labels, hints, etc.) to be displayed with the annotated property
 */
class TextAnnotation extends Annotation implements IDisplayAnnotation
{
  /**
   * @var $label string The label for the input, typically displayed displayed in front of the input.
   */
  public $label;
  
  /**
   * @var $abbr string An abbreviated version of the input label (for use in column headers or other places where space is limited).
   */
  public $abbr;
  
  /**
   * @var $info string A short description of the field, typically displayed on a form together with the input.
   */
  public $info;
  
  /**
   * @var $tip string Short instructions for the field, typically displayed in a tooltip balloon.
   */
  public $tip;
  
  /**
   * @var $help string Long instructions for the field, typically displayed in a popup window.
   */
  public $help;
  
  /**
   * @var $watermark string A short string, typically displayed in the input itself, while empty, or until it receives focus.
   */
  public $watermark;
}

/**
 * Defines various display-related metadata, such as grouping and ordering.
 */
class DisplayAnnotation extends Annotation implements IDisplayAnnotation
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

/**
 * Specifies how to display or format a property value.
 */
class FormatAnnotation extends Annotation implements IDisplayAnnotation
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

/**
 * Specifies the name of a view to use for display formatting.
 */
class ViewAnnotation extends Annotation implements IDisplayAnnotation
{
  public $name;
}

/**
 * Specifies the name of a view to use for rendering an input on a form.
 */
class EditorAnnotation extends Annotation implements IDisplayAnnotation
{
  public $name;
}
