<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Hooks;

use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

/**
 * This extension allows you to run an arbitrary test after every test in the current suite which is
 * extremely helpful when hunting down a functional test that causes your test fail.
 * When your test fails, the execution is stopped immediately and the name of problematic test is written to STDOUT.
 *
 * Example of usage: `MAUTIC_TEST_EXECUTE_TEST_AFTER="Fully\\Qualified\\Class\\NameTest" bin/phpunit`.
 */
class MauticExtension implements AfterTestHook, BeforeFirstTestHook
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

    public function executeBeforeFirstTest(): void
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            $x      = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $prefix = substr(str_shuffle(str_repeat($x, (int) ceil(4 / strlen($x)))), 1, 4).'_';
            define('MAUTIC_TABLE_PREFIX', 'prefix'.$prefix);
            echo 'using db prefix '.$prefix.PHP_EOL;
        }
    }
}
