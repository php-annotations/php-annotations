<?php

return array(
  'NoteAnnotation' => array(
    array('UsageAnnotation', 'class'=>true, 'property'=>true, 'method'=>true, 'inherited'=>true, 'multiple'=>true)
  ),
  'DocAnnotation' => array(
    array('UsageAnnotation', 'class'=>true)
  ),
  'TestDelegateAnnotation' => array(
    array('UsageAnnotation', 'class'=>true, 'multiple'=>true)
  ),
  'SingleAnnotation' => array(
    array('UsageAnnotation', 'property'=>true, 'multiple'=>false)
  ),
  'OverrideAnnotation' => array(
    array('UsageAnnotation', 'property'=>true, 'multiple'=>false, 'inherited'=>true)
  ),
  'SampleAnnotation' => array(
    array('UsageAnnotation', 'method'=>true)
  ),
  'UninheritableAnnotation' => array(
    array('UsageAnnotation', 'class'=>true, 'inherited'=>false)
  ),
  'TestBase' => array(
    array('DocAnnotation', 'value' => 1234),
    array('NoteAnnotation', "Applied to the TestBase class"),
    array('UninheritableAnnotation', 'test'=>'Test cannot inherit this annotation')
  ),
  'TestBase::$sample' => array(
    array('NoteAnnotation', "Applied to a TestBase member")
  ),
  'TestBase::$only_one' => array(
    array('SingleAnnotation', 'test'=>'one is okay'),
    array('SingleAnnotation', 'test'=>'two is one too many')
  ),
  'TestBase::$override_me' => array(
    array('OverrideAnnotation', 'test'=>'This will be overridden')
  ),
  'TestBase::$mixed' => array(
    array('NoteAnnotation', "First note annotation"),
    array('OverrideAnnotation', 'test'=>'This annotation should get filtered')
  ),
  'TestBase::run' => array(
    array('NoteAnnotation', "Applied to a hidden TestBase method"),
    array('SampleAnnotation', 'test'=>'This should get filtered')
  ),
  'Test' => array(
    array('NoteAnnotation', 'abc'),
    array('TestDelegateAnnotation', 'foo'),
    array('NoteAnnotation', '123'),
    array('TestDelegateAnnotation', 'bar'),
    array('NoteAnnotation', 
"Applied to the Test class (a)"
),
    array('NoteAnnotation', "And another one for good measure (b)")
  ),
  'Test::$hello' => array(
    array('NoteAnnotation', "Applied to a property")
  ),
  'Test::$override_me' => array(
    array('OverrideAnnotation', 'test'=>'This annotation overrides the one in TestBase')
  ),
  'Test::$mixed' => array(
    array('NoteAnnotation', "Second note annotation")
  ),
  'Test::run' => array(
    array('NoteAnnotation', "First Note Applied to the run() method"),
    array('NoteAnnotation', "And a second Note")
  ),
  'ValidationTest::$custom' => array(
    array('ValidateAnnotation', 'ValidationTest', 'validate')
  ),
  'ValidationTest::$url' => array(
    array('TypeAnnotation', 'url')
  ),
  'ValidationTest::$sex' => array(
    array('EnumAnnotation', array('M'=>'Male', 'F'=>'Female'))
  ),
  'ValidationTest::$age' => array(
    array('RequiredAnnotation', ),
    array('RangeAnnotation', 1,100)
  ),
  'ValidationTest::$long' => array(
    array('LengthAnnotation', 100,255)
  ),
  'ValidationTest::$lengthy' => array(
    array('LengthAnnotation', 255)
  ),
  'ValidationTest::$password' => array(
    array('LengthAnnotation', 6,10),
    array('PatternAnnotation', '/[a-z0-9_]+/')
  ),
);
