<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Hooks;

use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\AfterTestHook;

/**
 * This extension allows you to run an arbitrary test after every test in the current suite which is
 * extremely helpful when hunting down a functional test that causes your test fail.
 * When your test fails, the execution is stopped immediately and the name of problematic test is written to STDOUT.
 *
 * Example of usage: `MAUTIC_TEST_EXECUTE_TEST_AFTER="Fully\\Qualified\\Class\\NameTest" bin/phpunit`.
 */
class ExecuteTestAfterExtension implements AfterTestHook
{
    public function executeAfterTest(string $test, float $time): void
    {
        $testClass = getenv('MAUTIC_TEST_EXECUTE_TEST_AFTER');

        if (false === $testClass) {
            return;
        }

        $testSuite = new TestSuite();
        $testSuite->addTestSuite($testClass);
        $result = $testSuite->run();

        if (!$result->wasSuccessful()) {
            $failures = array_map(fn (TestFailure $testFailure) => $testFailure->getExceptionAsString(), array_merge($result->failures(), $result->errors()));

            exit(sprintf('The previous test was: "%s". Your test errored with: %s', $test, implode(PHP_EOL, $failures)));
        }
    }
}
