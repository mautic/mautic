<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Listeners;

use LogicException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use ReflectionMethod;

/**
 * Throws an exception if the test should be run in a separate process.
 */
class SeparateProcessListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function endTest(Test $test, float $time): void
    {
        if ($this->isTestRunInSeparateProcess($test)) {
            return;
        }

        $constants = $this->getMauticConstants();

        if ($constants) {
            throw new LogicException(sprintf('Test "%s::%s" must be run in a separate process as there were defined the following constants during the test execution: "%s".', get_class($test), $test->getName(), implode(', ', $constants)));
        }
    }

    private function isTestRunInSeparateProcess(Test $test): bool
    {
        $reflection = new ReflectionMethod($test, 'runInSeparateProcess');
        $reflection->setAccessible(true);

        return $reflection->invoke($test);
    }

    /**
     * @return string[]
     */
    private function getMauticConstants(): array
    {
        // get all user defined constants
        $constants = get_defined_constants(true)['user'] ?? [];

        if (!$constants) {
            return [];
        }

        // filter out only those that begins with MAUTIC_
        $constants = array_filter(array_keys($constants), fn (string $constant) => 0 === strpos($constant, 'MAUTIC_'));

        // remove non-problematic ones
        $constants = array_diff($constants, [
            'MAUTIC_DB_SERVER_VERSION',
            'MAUTIC_ENV',
            'MAUTIC_TABLE_PREFIX',
            'MAUTIC_VERSION',
        ]);

        return $constants;
    }
}
