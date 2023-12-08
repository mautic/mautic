<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Hooks;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;

/**
 * This extension outputs a list of slow test classes to the STDOUT.
 * Enable this extension by setting the following environmental variable `MAUTIC_TEST_LOG_SLOW_TESTS=1`.
 * You can set an optional threshold in seconds (e.g. `MAUTIC_TEST_SLOW_TESTS_THRESHOLD=1.5`).
 */
class SlowTestExtension implements AfterTestHook, AfterLastTestHook
{
    private bool $enabled;

    /**
     * Threshold in seconds.
     */
    private float $threshold;

    /**
     * @var array<string, float>
     */
    private $classes = [];

    public function __construct()
    {
        $this->enabled   = (bool) getenv('MAUTIC_TEST_LOG_SLOW_TESTS');
        $this->threshold = (float) (getenv('MAUTIC_TEST_SLOW_TESTS_THRESHOLD') ?: 2);
    }

    public function executeAfterTest(string $test, float $time): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($time <= $this->threshold) {
            return;
        }

        $class = substr($test, 0, strpos($test, '::'));

        if (!isset($this->classes[$class])) {
            $this->classes[$class] = 0;
        }

        $this->classes[$class] += $time;
    }

    public function executeAfterLastTest(): void
    {
        if (!$this->classes) {
            return;
        }

        arsort($this->classes);

        fwrite(STDOUT, PHP_EOL.'Slow test classes:'.PHP_EOL.var_export($this->classes, true));
    }
}
