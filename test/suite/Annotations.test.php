<?php
require_once __DIR__ . '/Annotations.case.php';
require_once __DIR__ . '/Annotations.Sample.case.php';

use mindplay\annotations\AnnotationFile;
use mindplay\annotations\AnnotationCache;
use mindplay\annotations\AnnotationParser;
use mindplay\annotations\AnnotationManager;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\Annotations;
use mindplay\annotations\Annotation;
use mindplay\test\annotations\Package;
use mindplay\test\lib\xTest;

/**
 * This class implements tests for core annotations
 */
class AnnotationsTest extends xTest
{
    public function __construct()
    {
        $cachePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'runtime';

        Annotations::$config = array(
            'cache' => new AnnotationCache($cachePath),
        );

        if (!is_writable($cachePath)) {
            die('cache path is not writable: ' . $cachePath);
        }

        // manually wipe out the cache:
        $pattern = Annotations::getManager()->cache->getRoot() . DIRECTORY_SEPARATOR . '*.annotations.php';

        foreach (glob($pattern) as $path) {
            unlink($path);
        }

        // disable some annotations not used during testing:
        Annotations::getManager()->registry['var'] = false;
        Annotations::getManager()->registry['param'] = false;
    }

    protected function testCanResolveAnnotationNames()
    {
        $manager = new AnnotationManager;
        $manager->namespace = ''; // look for annotations in the global namespace
        $manager->suffix = 'Annotation'; // use a suffix for annotation class-names

        $this->check(
            $manager->resolveName('test') === 'TestAnnotation',
            'should capitalize and suffix annotation names'
        );
        $this->check(
            $manager->resolveName('X\Y\Foo') === 'X\Y\FooAnnotation',
            'should suffix fully qualified annotation names'
        );

        $manager->registry['test'] = 'X\Y\Z\TestAnnotation';
        $this->check(
            $manager->resolveName('test') === 'X\Y\Z\TestAnnotation',
            'should respect registered annotation types'
        );
        $this->check(
            $manager->resolveName('Test') === 'X\Y\Z\TestAnnotation',
            'should ignore case of first letter in annotation names'
        );

        $manager->registry['test'] = false;
        $this->check($manager->resolveName('test') === false, 'should respect disabled annotation types');

        $manager->namespace = 'ABC';
        $this->check($manager->resolveName('hello') === 'ABC\HelloAnnotation', 'should default to standard namespace');
    }

    protected function testCanGetAnnotationFile()
    {
        // This test is for an internal API, so we need to perform some invasive maneuvers:

        $manager = Annotations::getManager();

        $manager_reflection = new ReflectionClass($manager);

        $method = $manager_reflection->getMethod('getAnnotationFile');
        $method->setAccessible(true);

        $class_reflection = new ReflectionClass('mindplay\test\Sample\SampleClass');

        // absolute path to the class-file used for testing
        $file_path = $class_reflection->getFileName();

        // Now get the AnnotationFile instance:

        /** @var AnnotationFile $file */
        $file = $method->invoke($manager, $file_path);

        $this->check($file instanceof AnnotationFile, 'should be an instance of AnnotationFile');
        $this->check(count($file->data) > 0, 'should contain Annotation data');
        $this->check($file->path === $file_path, 'should reflect path to class-file');
        $this->check($file->namespace === 'mindplay\test\Sample', 'should reflect namespace');
        $this->check($file->uses === array('SampleAlias' => 'mindplay\annotations\Annotation'), 'should reflect use-clause');
    }

    protected function testCanParseAnnotations()
    {
        $manager = new AnnotationManager;
        Package::register($manager);
        $manager->namespace = ''; // look for annotations in the global namespace
        $manager->suffix = 'Annotation'; // use a suffix for annotation class-names

        $parser = $manager->getParser();

        $source = "
            <?php

            namespace foo\\bar;

            use
                baz\\Hat as Zing,
                baz\\Zap;

            /**
             * @doc 123
             * @note('abc')
             * @required
             * @note('xyz');
             */
            class Sample {
                public function test()
                {
                    \$var = null;

                    \$test = function () use (\$var) {
                        // this inline function is here to assert that the parser
                        // won't pick up the use-clause of an inline function
                    };
                }
            }
        ";

        $code = $parser->parse($source, 'inline-test');
        $test = eval($code);

        $this->check($test['#namespace'] === 'foo\bar', 'file namespace should be parsed and cached');
        $this->check($test['#uses'] === array('Zing' => 'baz\Hat', 'Zap' => 'baz\Zap'), 'use-clauses should be parsed and cached: ' . var_export($test['#uses'], true));

        $this->check($test['foo\bar\Sample'][0]['#name'] === 'doc', 'first annotation is an @doc annotation');
        $this->check($test['foo\bar\Sample'][0]['#type'] === 'DocAnnotation', 'first annotation is a DocAnnotation');
        $this->check($test['foo\bar\Sample'][0]['value'] === 123, 'first annotation has the value 123');

        $this->check($test['foo\bar\Sample'][1]['#name'] === 'note', 'second annotation is an @note annotation');
        $this->check($test['foo\bar\Sample'][1]['#type'] === 'NoteAnnotation', 'second annotation is a NoteAnnotation');
        $this->check($test['foo\bar\Sample'][1][0] === 'abc', 'value of second annotation is "abc"');

        $this->check(
            $test['foo\bar\Sample'][2]['#type'] === 'mindplay\test\annotations\RequiredAnnotation',
            'third annotation is a RequiredAnnotation'
        );

        $this->check($test['foo\bar\Sample'][3]['#type'] === 'NoteAnnotation', 'last annotation is a NoteAnnotation');
        $this->check($test['foo\bar\Sample'][3][0] === 'xyz', 'value of last annotation is "xyz"');
    }

    protected function testCanGetStaticAnnotationManager()
    {
        if (Annotations::getManager() instanceof AnnotationManager) {
            $this->pass();
        } else {
            $this->fail();
        }
    }

    protected function testCanGetAnnotationUsage()
    {
        $usage = Annotations::getUsage('NoteAnnotation');

        $this->check($usage->class === true);
        $this->check($usage->property === true);
        $this->check($usage->method === true);
        $this->check($usage->inherited === true);
        $this->check($usage->multiple === true);
    }

    public function testAnnotationWithNonUsageAndUsageAnnotations()
    {
        $this->setExpectedException(
            'mindplay\annotations\AnnotationException',
            "the class 'UsageAndNonUsageAnnotation' must have exactly one UsageAnnotation (no other Annotations are allowed)"
        );

        Annotations::getUsage('UsageAndNonUsageAnnotation');
    }

    public function testAnnotationWithSingleNonUsageAnnotation()
    {
        $this->setExpectedException(
            'mindplay\annotations\AnnotationException',
            "the class 'SingleNonUsageAnnotation' must have exactly one UsageAnnotation (no other Annotations are allowed)"
        );

        Annotations::getUsage('SingleNonUsageAnnotation');
    }

    public function testUsageAnnotationIsInherited()
    {
        $usage = Annotations::getUsage('InheritUsageAnnotation');
        $this->check($usage->method === true);
    }

    protected function testCanGetClassAnnotations()
    {
        $ann = Annotations::ofClass('Test');

        $this->check(count($ann) > 0);
    }

    protected function testCanGetMethodAnnotations()
    {
        $ann = Annotations::ofMethod('Test', 'run');

        $this->check(count($ann) > 0);
    }

    protected function testCanGetPropertyAnnotations()
    {
        $ann = Annotations::ofProperty('Test', 'sample');

        $this->check(count($ann) > 0);
    }

    public function testGetAnnotationsFromNonExistingPropertyOfExistingClass()
    {
        $this->setExpectedException(
            'mindplay\annotations\AnnotationException',
            'undefined property Test::$nonExisting'
        );
        Annotations::ofProperty('Test', 'nonExisting');
    }

    protected function testCanGetFilteredPropertyAnnotations()
    {
        $anns = Annotations::ofProperty('Test', 'mixed', 'NoteAnnotation');

        if (!count($anns)) {
            $this->fail('No annotations found');
            return;
        }

        foreach ($anns as $ann) {
            if (!$ann instanceof NoteAnnotation) {
                $this->fail();
            }
        }

        $this->pass();
    }

    protected function testCanGetFilteredClassAnnotations()
    {
        $anns = Annotations::ofClass('TestBase', 'NoteAnnotation');

        if (!count($anns)) {
            $this->fail('No annotations found');
            return;
        }

        foreach ($anns as $ann) {
            if (!$ann instanceof NoteAnnotation) {
                $this->fail();
            }
        }

        $this->pass();
    }

    protected function testCanGetFilteredMethodAnnotations()
    {
        $anns = Annotations::ofMethod('TestBase', 'run', 'NoteAnnotation');

        if (!count($anns)) {
            $this->fail('No annotations found');
            return;
        }

        foreach ($anns as $ann) {
            if (!$ann instanceof NoteAnnotation) {
                $this->fail();
            }
        }

        $this->pass();
    }

    protected function testCanGetInheritedClassAnnotations()
    {
        $anns = Annotations::ofClass('Test');

        foreach ($anns as $ann) {
            if ($ann->note == 'Applied to the TestBase class') {
                $this->pass();
                return;
            }
        }

        $this->fail();
    }

    protected function testCanGetInheritedMethodAnnotations()
    {
        $anns = Annotations::ofMethod('Test', 'run');

        foreach ($anns as $ann) {
            if ($ann->note == 'Applied to a hidden TestBase method') {
                $this->pass();
                return;
            }
        }

        $this->fail();
    }

    protected function testCanGetInheritedPropertyAnnotations()
    {
        $anns = Annotations::ofProperty('Test', 'sample');

        foreach ($anns as $ann) {
            if ($ann->note == 'Applied to a TestBase member') {
                $this->pass();
                return;
            }
        }

        $this->fail();
    }

    protected function testDoesNotInheritUninheritableAnnotations()
    {
        $anns = Annotations::ofClass('Test');

        if (count($anns) == 0) {
            $this->fail();
            return;
        }

        foreach ($anns as $ann) {
            if ($ann instanceof UninheritableAnnotation) {
                $this->fail();
                return;
            }
        }

        $this->pass();
    }

    protected function testThrowsExceptionIfSingleAnnotationAppliedTwice()
    {
        try {
            $anns = Annotations::ofProperty('Test', 'only_one');
        } catch (AnnotationException $e) {
            $this->pass();
            return;
        }

        $this->fail('Did not throw expected exception');
    }

    protected function testCanOverrideSingleAnnotation()
    {
        $anns = Annotations::ofProperty('Test', 'override_me');

        if (count($anns) != 1) {
            $this->fail(count($anns) . ' annotations found - expected 1');
            return;
        }

        $ann = reset($anns);

        if ($ann->test != 'This annotation overrides the one in TestBase') {
            $this->fail();
        } else {
            $this->pass();
        }
    }

    protected function testCanHandleEdgeCaseInParser()
    {
        // an edge-case was found in the parser - this test asserts that a php-doc style
        // annotation with no trailing characters after it will be parsed correctly.

        $anns = Annotations::ofClass('TestBase', 'DocAnnotation');

        $this->check(count($anns) == 1, 'one DocAnnotation was expected - found ' . count($anns));
    }

    protected function testCanHandleNamespaces()
    {
        // This test asserts that a namespaced class can be annotated, that annotations can
        // be namespaced, and that asking for annotations of a namespaced annotation-type
        // yields the expected result.

        $anns = Annotations::ofClass('mindplay\test\Sample\SampleClass', 'mindplay\test\Sample\SampleAnnotation');

        $this->check(count($anns) == 1, 'one SampleAnnotation was expected - found ' . count($anns));
    }

    protected function testCanUseAnnotationsInDefaultNamespace()
    {
        $manager = new AnnotationManager();
        $manager->namespace = 'mindplay\test\Sample';
        $manager->cache = false;

        $anns = $manager->getClassAnnotations('mindplay\test\Sample\AnnotationInDefaultNamespace', 'mindplay\test\Sample\SampleAnnotation');

        $this->check(count($anns) == 1, 'one SampleAnnotation was expected - found ' . count($anns));
    }

    protected function testCanIgnoreAnnotations()
    {
        $manager = new AnnotationManager();
        $manager->namespace = 'mindplay\test\Sample';
        $manager->cache = false;

        $manager->registry['ignored'] = false;

        $anns = $manager->getClassAnnotations('mindplay\test\Sample\IgnoreMe');

        $this->check(count($anns) == 0, 'the @ignored annotation should be ignored');
    }

    protected function testCanUseAnnotationAlias()
    {
        $manager = new AnnotationManager();
        $manager->namespace = 'mindplay\test\Sample';
        $manager->cache = false;

        $manager->registry['aliased'] = 'mindplay\test\Sample\SampleAnnotation';

        /** @var Annotation[] $anns */
        $anns = $manager->getClassAnnotations('mindplay\test\Sample\AliasMe');

        $this->check(count($anns) == 1, 'the @aliased annotation should be aliased');
        $this->check(get_class($anns[0]) == 'mindplay\test\Sample\SampleAnnotation', 'returned @aliased annotation should map to mindplay\test\Sample\SampleAnnotation');
    }

    protected function testCanFindAnnotationsByAlias()
    {
        $ann = Annotations::ofProperty('TestBase', 'sample', '@note');

        $this->check(count($ann) === 1, 'TestBase::$sample has one @note annotation');
    }

    protected function testParseUserDefinedClasses()
    {
        $annotations = Annotations::ofClass('TestClassExtendingUserDefined', '@note');

        $this->check(count($annotations) == 2, 'TestClassExtendingUserDefined has two note annotations.');
    }

    protected function testDoNotParseCoreClasses()
    {
        $annotations = Annotations::ofClass('TestClassExtendingCore', '@note');

        $this->check(count($annotations) == 1, 'TestClassExtendingCore has one note annotations.');
    }

    protected function testDoNotParseExtensionClasses()
    {
        $annotations = Annotations::ofClass('TestClassExtendingExtension', '@note');

        $this->check(count($annotations) == 1, 'TestClassExtendingExtension has one note annotations.');
    }

    public function testGetAnnotationsFromNonExistingClass()
    {
        $this->setExpectedException('mindplay\annotations\AnnotationException', 'Unable to read annotations from an undefined class "NonExistingClass"');
        Annotations::ofClass('NonExistingClass', '@note');
    }

    public function testGetAnnotationsFromAnInterface()
    {
        $this->setExpectedException('mindplay\annotations\AnnotationException', 'Reading annotations from interface/trait "TestInterface" is not supported');
        Annotations::ofClass('TestInterface', '@note');
    }

    public function testGetAnnotationsFromTrait()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->pass();
            return;
        }

        eval('trait TestTrait { }');
        $this->setExpectedException('mindplay\annotations\AnnotationException', 'Reading annotations from interface/trait "TestTrait" is not supported');
        Annotations::ofClass('TestTrait', '@note');
    }

}

interface TestInterface {

}

return new AnnotationsTest;
