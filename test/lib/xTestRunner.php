<?php
namespace mindplay\test\lib;

/**
 * This class implements a very simple test suite runner and code
 * coverage benchmarking (where supported by the xdebug extension).
 */
class xTestRunner
{
    private $rootpath;

    /**
     * Code coverage information tracker.
     *
     * @var \PHP_CodeCoverage
     */
    private $coverage;

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

        try {
            $this->coverage = new \PHP_CodeCoverage();
            $this->coverage->filter()->addDirectoryToWhitelist($rootpath);
        } catch (\PHP_CodeCoverage_Exception $e) {
            // can't collect coverage
        }
    }

    /**
     * Starts coverage information collection for a test.
     *
     * @param string $testName Test name.
     * @return void
     */
    public function startCoverageCollector($testName)
    {
        if (isset($this->coverage)) {
            $this->coverage->start($testName);
        }
    }

    /**
     * Stops coverage information collection.
     *
     * @return void
     */
    public function stopCoverageCollector()
    {
        if (isset($this->coverage)) {
            $this->coverage->stop();
        }
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
        $this->header();

        echo '<h4>Codebase: ' . $this->rootpath . '</h4>';
        echo '<h4>Test Suite: ' . $pattern . '</h4>';

        foreach (glob($pattern) as $path) {
            $test = require($path);

            if (!$test instanceof xTest) {
                throw new \Exception("'{$path}' is not a valid unit test");
            }

            $test->run($this);
        }

        $this->createCodeCoverageReport();
        $this->footer();
    }

    /**
     * Creates code coverage report.
     *
     * @return void
     */
    protected function createCodeCoverageReport()
    {
        if (isset($this->coverage)) {
            $writer = new \PHP_CodeCoverage_Report_HTML;
            $writer->process($this->coverage, FULL_PATH . '/test/runtime/coverage');

            echo '<a href="runtime/coverage">Code coverage report</a>';
        } else {
            echo '<h3>Code coverage analysis unavailable</h3><p>To enable code coverage, the xdebug php module must be installed and enabled.</p>';
        }
    }
}
