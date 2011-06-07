<?php

require_once 'suite/Annotations.case.php';
require_once 'suite/Annotations.Sample.case.php';

use Annotation\AnnotationParser;
use Annotation\AnnotationManager;
use Annotation\AnnotationException;
use Annotation\Annotations;

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
  
  protected function testCanResolveAnnotationNames()
  {
    $manager = new AnnotationManager;
    $manager->namespace = ''; // look for annotations in the global namespace
    $manager->suffix = 'Annotation'; // use a suffix for annotation class-names
    
    $this->check($manager->resolveName('test') === 'TestAnnotation', 'should capitalize and suffix annotation names');
    $this->check($manager->resolveName('X\Y\Foo') === 'X\Y\FooAnnotation', 'should suffix fully qualified annotation names');
    
    $manager->registry['test'] = 'X\Y\Z\TestAnnotation';
    $this->check($manager->resolveName('test') === 'X\Y\Z\TestAnnotation', 'should respect registered annotation types');
    $this->check($manager->resolveName('Test') === 'X\Y\Z\TestAnnotation', 'should ignore case of first letter in annotation names');
    
    $manager->registry['test'] = false;
    $this->check($manager->resolveName('test') === false, 'should respect disabled annotation types');
    
    $manager->namespace = 'ABC';
    $this->check($manager->resolveName('hello') === 'ABC\HelloAnnotation', 'should default to standard namespace');
  }
  
  protected function testCanParseAnnotations()
  {
    $manager = new AnnotationManager;
    $manager->namespace = ''; // look for annotations in the global namespace
    $manager->suffix = 'Annotation'; // use a suffix for annotation class-names
    
    $parser = $manager->getParser();
    
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
    
    $this->check($test['Sample'][2][0] === 'Annotation\Standard\RequiredAnnotation', 'third annotation is a RequiredAnnotation');
    
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
    // an edge-case was found in the parser - this test asserts that a php-doc style
    // annotation with no trailing characters after it will be parsed correctly.
    
    $anns = Annotations::ofClass('TestBase', 'DocAnnotation');
    
    $this->check(count($anns)==1, 'one DocAnnotation was expected - found '.count($anns));
  }
  
  protected function testCanHandleNamespaces()
  {
    // This test asserts that a namespaced class can be annotated, that annotations can
    // be namespaced, and that asking for annotations of a namespaced annotation-type
    // yields the expected result.
    
    $anns = Annotations::ofClass('Sample\SampleClass', 'Sample\SampleAnnotation');
    
    $this->check(count($anns)==1, 'one SampleAnnotation was expected - found '.count($anns));
  }
  
  protected function testCanUseAnnotationsInDefaultNamespace()
  {
    $manager = new AnnotationManager();
    $manager->namespace = 'Sample';
    $manager->autoload = false;
    $manager->cachePath = Annotations::getManager()->cachePath;
    $manager->cacheSeed = 'abc123';
    
    $anns = $manager->getClassAnnotations('Sample\AnnotationInDefaultNamespace', 'Sample\SampleAnnotation');
    
    $this->check(count($anns)==1, 'one SampleAnnotation was expected - found '.count($anns));
  }
  
  protected function testCanIgnoreAnnotations()
  {
    $manager = new AnnotationManager();
    $manager->namespace = 'Sample';
    $manager->autoload = false;
    $manager->cachePath = Annotations::getManager()->cachePath;
    $manager->cacheSeed = 'xyz';
    
    $manager->registry['ignored'] = false;
    
    $anns = $manager->getClassAnnotations('Sample\IgnoreMe');
    
    $this->check(count($anns)==0, 'the @ignored annotation should be ignored');
  }
  
  protected function testCanUseAnnotationAlias()
  {
    $manager = new AnnotationManager();
    $manager->namespace = 'Sample';
    $manager->autoload = false;
    $manager->cachePath = Annotations::getManager()->cachePath;
    $manager->cacheSeed = '12345678';
    
    $manager->registry['aliased'] = 'Sample\SampleAnnotation';
    
    $anns = $manager->getClassAnnotations('Sample\AliasMe');
    
    $this->check(count($anns)==1, 'the @aliased annotation should be aliased');
  }
}

return new AnnotationsTest;
