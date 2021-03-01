<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test\Hooks;

use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;

/**
 * This extension allows you to run an arbitrary test after every test in the current suite which is
 * extremely helpful when hunting down a functional test that causes your test fail.
 * When your test fails, the execution is stopped immediately and the name of problematic test is written to STDOUT.
 *
 * Example of usage: `MAUTIC_TEST_EXECUTE_TEST_AFTER="Fully\\Qualified\\Class\\NameTest" bin/phpunit`.
 */
class ExecuteTestAfterExtension implements AfterTestHook, BeforeTestHook
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
            $failures = array_map(function (TestFailure $testFailure) {
                return $testFailure->getExceptionAsString();
            }, array_merge($result->failures(), $result->errors()));

            exit(sprintf('The previous test was: "%s". Your test errored with: %s', $test, implode(PHP_EOL, $failures)));
        }
    }

    public function executeBeforeTest(string $test): void
    {
        // Without implementing this method the method self::executeAfterTest() is never invoked. There must be a bug in PHPUnit 7.5.20.
    }
}
