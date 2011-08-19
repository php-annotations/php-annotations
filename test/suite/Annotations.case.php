<?php

/*
 * TEST CASE: Sample Annotations
 */

use Annotation\Annotation;
use Annotation\IAnnotationParser;
use Annotation\AnnotationException;

/**
 * @usage('class'=>true, 'property'=>true, 'method'=>true, 'inherited'=>true, 'multiple'=>true)
 */
class NoteAnnotation extends Annotation
{
  public $note;
  
  public function initAnnotation($params)
  {
    $this->_map($params, array('note'));
    
    if (!isset($this->note))
      throw new AnnotationException("NoteAnnotation requires a note property");
  }
}

/**
 * @usage('class'=>true)
 */
class DocAnnotation extends Annotation implements IAnnotationParser
{
  public $value;
  
  public static function parseAnnotation($value)
  {
    return array('value' => intval($value));
  }
}

/**
 * @usage('property'=>true, 'multiple'=>false)
 */
class SingleAnnotation extends Annotation
{
  public $test;
}

/**
 * @usage('property'=>true, 'multiple'=>false, 'inherited'=>true)
 */
class OverrideAnnotation extends Annotation
{
  public $test;
}

/**
 * @usage('method'=>true)
 */
class SampleAnnotation extends Annotation
{
  public $test;
}

/**
 * @usage('class'=>true, 'inherited'=>false)
 */
class UninheritableAnnotation extends Annotation
{
  public $test;
}

/**
 * TEST CASE: Sample Classes
 *
 * @doc 1234 (this is a sample PHP-DOC style annotation)
 */

/**
 * @note("Applied to the TestBase class")
 * @uninheritable('test'=>'Test cannot inherit this annotation')
 */
class TestBase
{
  /**
   * @note("Applied to a TestBase member")
   */
  private $sample='test';
  
  /**
   * @single('test'=>'one is okay')
   * @single('test'=>'two is one too many')
   */
  private $only_one;
  
  /**
   * override('test'=>'This will be overridden')
   */
  private $override_me;
  
  /**
   * @note("First note annotation")
   * @override('test'=>'This annotation should get filtered')
   */
  private $mixed;
  
  /**
   * @note("Applied to a hidden TestBase method")
   * @sample('test'=>'This should get filtered')
   */
  public function run()
  {
  }
}

/**
 * A sample class with NoteAttributes applied to the source code:
 *
 * @Note(
 *   "Applied to the Test class (a)"
 * )
 * 
 * @Note("And another one for good measure (b)")
 */
class Test extends TestBase
{
  /**
   * @Note("Applied to a property")
   */
  public $hello='World';
  
  /**
   * @Override('test'=>'This annotation overrides the one in TestBase')
   */
  private $override_me;
  
  /**
   * @Note("Second note annotation")
   */
  private $mixed;
  
  /**
   * @Note("First Note Applied to the run() method")
   * @Note("And a second Note")
   */
  public function run()
  {
  }
}

class ValidationTest
{
  /**
   * @Validate('ValidationTest', 'validate')
   */
  public $custom;
  
  /**
   * @Type('url')
   */
  public $url;
  
  /**
   * @Enum(array('M'=>'Male', 'F'=>'Female'))
   */
  public $sex;
  
  /**
   * @Required()
   * @Range(1,100)
   */
  public $age;
  
  /**
   * @Length(100,255)
   */
  public $long;
  
  /**
   * @Length(255)
   */
  public $lengthy;
  
  /**
   * @Length(6,10)
   * @Pattern('/[a-z0-9_]+/')
   */
  public $password;
  
  public function validate()
  {
    return true;
  }
}
 