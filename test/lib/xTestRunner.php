<?php
namespace mindplay\test\lib;

/**
 * This class implements a very simple test suite runner and code
 * coverage benchmarking (where supported by the xdebug extension).
 */
class xTestRunner
{
    private $rootpath;
    private $xdebug;

    /**
     * @param string $rootpath The absolute path to the root folder of the test suite.
     *
     * @throws \Exception
     */
    public function __construct($rootpath)
    {
        if (!is_dir($rootpath)) {
            throw new \Exception("{$rootpath} is not a directory");
        }

        $this->rootpath = $rootpath;

        $this->xdebug = function_exists('xdebug_start_code_coverage');
    }

    /**
     * Prints the header before the test output
     */
    protected function header()
    {
        echo '<html>
                <head>
                    <title>Unit Tests</title>
                    <style type="text/css">
                        table { border-collapse:collapse; }
                        td, th { text-align:left; padding:2px 6px; border:solid 1px #aaa; }
                    </style>
                </head>
                <body>
                    <h2>Unit Tests</h2>';
    }

    /**
     * Prints the footer after the test output
     */
    protected function footer()
    {
        echo '</body></html>';
    }

    /**
     * Runs a suite of unit tests
     *
     * @param string $pattern A filename pattern compatible with glob()
     *
     * @throws \Exception
     */
    public function run($pattern)
    {
        if ($this->xdebug) {
            xdebug_stop_code_coverage(true);
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED + XDEBUG_CC_DEAD_CODE);
        }

        $this->header();

        echo '<h4>Codebase: ' . $this->rootpath . '</h4>';
        echo '<h4>Test Suite: ' . $pattern . '</h4>';

        foreach (glob($pattern) as $path) {
            $test = require($path);

            if (!$test instanceof xTest) {
                throw new \Exception("'{$path}' is not a valid unit test");
            }

            $test->run();
        }

        if ($this->xdebug) {
            xdebug_stop_code_coverage(false);

            // we can safely ignore uncovered empty lines, closing braces and else-clauses that don't have a statement
            $uncovered = array('', '}', 'else');

            foreach (xdebug_get_code_coverage() as $path => $lines) {
                if (substr($path, 0, strlen($this->rootpath)) == $this->rootpath && strpos($path, "eval()'d code") === false) {
                    $relpath = substr($path, strlen($this->rootpath) + 1);

                    $file = file($path);

                    ob_start();
                    foreach ($lines as $line => $coverage) {
                        if ($coverage !== 1 && !in_array(trim($file[$line]), $uncovered)) {
                            echo '<span style="color:#' . ($coverage == -1 ? 'f00' : '888') . '">' . sprintf('%5d', $line + 1) . ' : ' . $file[$line] . "</span>";
                        }
                    }
                    $report = ob_get_clean();

                    if ($report) {
                        echo '<h3>Uncovered code in: ' . $relpath . '</h3>';
                        echo '<pre>' . $report . '</pre>';
                    } // else echo '<h3>100% Code coverage in: '.$relpath.'</h3>';
                }
            }
        } else {
            echo '<h3>Code coverage analysis unavailable</h3><p>To enable code coverage, the xdebug php module must be installed and enabled.</p>';
        }

        $this->footer();
    }
}
