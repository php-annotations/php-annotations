<?php

/**
 * Defines various text (labels, hints, etc.) to be displayed with the annotated property
 */
class TextAnnotation extends Annotation
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
