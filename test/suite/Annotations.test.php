<?php

require_once '../lib/Annotations.php';
require_once '../lib/AnnotationManager.php';
require_once '../lib/AnnotationParser.php';
require_once 'suite/Annotations.case.php';

/**
 * This class implements tests for core annotations
 */
class AnnotationsTest extends xTest
{
  public function __construct()
  {
    Annotations::$config = array(
      'autoload' => false, // not using an autoloader during unit tests
      'cachePath' => dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'runtime', // turn caching on (or else AnnotationManager will generate E_NOTICE)
    );
    
    // manually wipe out the cache:
    foreach (glob(Annotations::getManager()->cachePath.DIRECTORY_SEPARATOR.'*.annotations.php') as $path)
      unlink($path);
  }
  
  protected function testCanParseAnnotations()
  {
    $parser = new AnnotationParser;
    $parser->suffix = 'Annotation';
    
    $source = "
      <?php
      /**
       * @doc 123
       * @note('abc')
       * @required
       * @note('xyz');
       */
      class Sample {}
    ";
    
    $code = $parser->parse($source, 'inline-test');
    
    $test = eval($code);
    
    $this->check($test['Sample'][0][0] === 'DocAnnotation', 'first annotation is a DocAnnotation');
    $this->check($test['Sample'][0]['value'] === 123, 'first annotation has the value 123');
    
    $this->check($test['Sample'][1][0] === 'NoteAnnotation', 'second annotation is a NoteAnnotation');
    $this->check($test['Sample'][1][1] === 'abc', 'value of second annotation is "abc"');
    
    $this->check($test['Sample'][2][0] === 'RequiredAnnotation', 'third annotation is a RequiredAnnotation');
    
    $this->check($test['Sample'][3][0] === 'NoteAnnotation', 'last annotation is a NoteAnnotation');
    $this->check($test['Sample'][3][1] === 'xyz', 'value of last annotation is "xyz"');
  }
  
  protected function testCanGetStaticAnnotationManager()
  {
    if (Annotations::getManager() instanceof AnnotationManager)
      $this->pass();
    else
      $this->fail();
  }
  
  protected function testCanGetAnnotationUsage()
  {
    $usage = Annotations::getUsage('NoteAnnotation');
    $this->check($usage->class===true);
    $this->check($usage->property===true);
    $this->check($usage->method===true);
    $this->check($usage->inherited===true);
    $this->check($usage->multiple===true);
  }
  
  protected function testCanGetClassAnnotations()
  {
    $ann = Annotations::ofClass('Test');
    $this->check(count($ann)>0);
  }
  
  protected function testCanGetMethodAnnotations()
  {
    $ann = Annotations::ofMethod('Test', 'run');
    $this->check(count($ann)>0);
  }
  
  protected function testCanGetPropertyAnnotations()
  {
    $ann = Annotations::ofProperty('Test', 'sample');
    $this->check(count($ann)>0);
  }
  
  protected function testCanGetFilteredPropertyAnnotations()
  {
    $anns = Annotations::ofProperty('Test', 'mixed', 'NoteAnnotation');
    if (!count($anns))
      return $this->fail('No annotations found');
    foreach ($anns as $ann)
      if (!$ann instanceof NoteAnnotation)
        $this->fail();
    $this->pass();
  }
  
  protected function testCanGetFilteredClassAnnotations()
  {
    $anns = Annotations::ofClass('TestBase', 'NoteAnnotation');
    if (!count($anns))
      return $this->fail('No annotations found');
    foreach ($anns as $ann)
      if (!$ann instanceof NoteAnnotation)
        $this->fail();
    $this->pass();
  }
  
  protected function testCanGetFilteredMethodAnnotations()
  {
    $anns = Annotations::ofMethod('TestBase', 'run', 'NoteAnnotation');
    if (!count($anns))
      return $this->fail('No annotations found');
    foreach ($anns as $ann)
      if (!$ann instanceof NoteAnnotation)
        $this->fail();
    $this->pass();
  }
  
  protected function testCanGetInheritedClassAnnotations()
  {
    $anns = Annotations::ofClass('Test');
    foreach ($anns as $ann)
      if ($ann->note == 'Applied to the TestBase class')
        return $this->pass();
    $this->fail();
  }
  
  protected function testCanGetInheritedMethodAnnotations()
  {
    $anns = Annotations::ofMethod('Test', 'run');
    foreach ($anns as $ann)
      if ($ann->note == 'Applied to a hidden TestBase method')
        return $this->pass();
    $this->fail();
  }
  
  protected function testCanGetInheritedPropertyAnnotations()
  {
    $anns = Annotations::ofProperty('Test', 'sample');
    foreach ($anns as $ann)
      if ($ann->note == 'Applied to a TestBase member')
        return $this->pass();
    $this->fail();
  }
  
  protected function testDoesNotInheritUninheritableAnnotations()
  {
    $anns = Annotations::ofClass('Test');
    if (count($anns)==0)
      $this->fail();
    foreach ($anns as $ann)
      if ($ann instanceof UninheritableAnnotation)
        $this->fail();
    $this->pass();
  }
  
  protected function testThrowsExceptionIfSingleAnnotationAppliedTwice()
  {
    try
    {
      $anns = Annotations::ofProperty('Test', 'only_one');
    }
    catch (AnnotationException $e)
    {
      return $this->pass();
    }
    $this->fail('Did not throw expected exception');
  }
  
  protected function testCanOverrideSingleAnnotation()
  {
    $anns = Annotations::ofProperty('Test', 'override_me');
    
    if (count($anns)!=1)
      return $this->fail(count($anns).' annotations found - expected 1');
    
    $ann = reset($anns);
    
    if ($ann->test!='This annotation overrides the one in TestBase')
      $this->fail();
    else
      $this->pass();
  }
  
  protected function testCanDelegateAnnotations()
  {
    $anns = Annotations::ofProperty('Test', 'foo');
    
    if (count($anns)!=1)
      return $this->fail(count($anns).' annotations found - expected 1');
    
    $ann = reset($anns);
    
    if ($ann->note != 'abc')
      return $this->fail('value of delegated annotation does not match the expected value');
    
    $anns = Annotations::ofProperty('Test', 'bar');
    
    if (count($anns)!=1)
      return $this->fail(count($anns).' annotations found - expected 1');
    
    $ann = reset($anns);
    
    if ($ann->note != '123')
      return $this->fail('value of delegated annotation does not match the expected value');
    
    $this->pass('delegation tests passed');
  }
  
  protected function testCanHandleEdgeCaseInParser()
  {
    // an edge-case was found in the parser - this test ensures that a php-doc style
    // annotation with no trailing characters after it will are parsed correctly.
    
    $anns = Annotations::ofClass('TestBase', 'DocAnnotation');
    
    $this->check(count($anns)==1, 'one DocAnnotation was expected - found '.count($anns));
  }
}

return new AnnotationsTest;
