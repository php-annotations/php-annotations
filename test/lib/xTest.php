<?php
namespace test\lib;

/**
 * A base class to support simple unit tests.
 *
 * To define a test, declare a method with no arguments, prefixing it's name with "test",
 * for example: function testCanReadXmlFeed().
 *
 * If you declare an init() method, this will be run once before proceeding with the tests.
 *
 * If you declare a setup() and/or teardown() method, these will be run before/after each test.
 *
 * @todo document missing parameters and return-types
 */
abstract class xTest
{
    private $result;

    /**
     * Runs this test
     */
    public function run()
    {
        $class = get_class($this);

        echo '<h3>' . htmlspecialchars($class) . '</h3>';
        echo '<table id="' . $class . '-results"><tr><th>Test</th><th>Result</th></tr>';

        $reflection = new \ReflectionClass(get_class($this));
        $methods = $reflection->getMethods();

        $passed = 0;
        $count = 0;

        if (method_exists($this, 'init')) {
            try {
                $this->init();
            } catch (\Exception $e) {
                echo '<tr style="color:white; background:red;"><td>init() failed</td><td><pre>' . $e . '</pre></td></tr></table>';
                return;
            }
        }

        foreach ($methods as $method) {
            if (substr($method->name, 0, 4) == 'test') {
                $this->result = null;

                $test = $method->name;

                $name = substr($test, 4);

                if (count($_GET) && @$_GET[$name] !== '') {
                    continue;
                }

                if (method_exists($this, 'setup')) {
                    $this->setup();
                }

                try {
                    $this->$test();
                } catch (\Exception $e) {
                    if (!($e instanceof xTestException)) {
                        $this->result = (string)$e;
                    }
                }

                if ($this->result !== true) {
                    $color = 'red';
                } elseif ($this->result === null) {
                    $color = 'blue';
                } else {
                    $color = 'green';
                }

                if ($this->result === true) {
                    $result = 'PASS';
                } elseif ($this->result === null) {
                    $result = 'FAIL: Incomplete Test';
                } else {
                    $result = 'FAIL' . (is_string($this->result) ? ': ' . $this->result : '');
                }

                $count++;

                if ($this->result === true) {
                    $passed++;
                }

                if (method_exists($this, 'teardown')) {
                    $this->teardown();
                }

                echo '<tr style="color:white; background:' . $color . '"><td>(' . $method->getStartLine() . ') <a style="color:white" href="?' . $name . '">' . preg_replace('/([A-Z])/', ' \1', $name) . '</a></td><td><pre>' . htmlspecialchars($result) . '</pre></td></tr>';
            }
        }

        echo '<tr style="background-color:gray; color:white"><tr><th>' . $count . ' Tests</th><th>';

        if ($passed == $count) {
            echo 'All Tests Passed';
        } else {
            echo ($count - $passed) . ' Tests Failed';
        }

        echo '</th></tr>';

        echo '</table>';

        if ($passed == $count) {
            echo '<h4 id="' . $class . '-toggle" style="cursor:pointer" onclick="' . "document.getElementById('{$class}-results').style.display='table'; document.getElementById('{$class}-toggle').style.display='none'; return false;" . '">&raquo; All Tests Passed</h4><script type="text/javascript">document.getElementById("' . $class . '-results").style.display="none";</script>';
        }
    }

    /**
     * Calling this method during a test flags a test as passed or failed.
     *
     * @param bool        $pass   bool If this expression evaluates as true, the test is passed
     * @param bool|string $result string Optional - if supplied, should contain a brief description of why the test failed
     */
    protected function check($pass, $result = false)
    {
        if ($pass) {
            $this->pass();
        } else {
            $this->fail($result);
        }
    }

    /**
     * Calling this method during a test manually flags a test as passed
     */
    protected function pass()
    {
        if ($this->result === null) {
            $this->result = true;
        }
    }

    /**
     * Calling this method during a test manually flags a test as failed
     *
     * @param bool|string $result string Optional - if supplied, should contain a brief description of why the test failed
     *
     * @throws xTestException
     */
    protected function fail($result = false)
    {
        $this->result = $result;
        throw new xTestException();
    }

    /**
     * Calling this method during a test flags a test as passed if two values are exactly (===) the same.
     *
     * @param mixed       $a    mixed Any value
     * @param mixed       $b    mixed Any value - if exactly the same as $a, the test is passed
     * @param bool|string $fail string Optional - if supplied, should contain a brief description of why the test failed
     */
    protected function eq($a, $b, $fail = false)
    {
        if ($a === $b) {
            $this->pass();
        } else {
            $this->fail($fail === false ? var_export($a, true) . ' !== ' . var_export($b, true) : $fail);
        }
    }
}
