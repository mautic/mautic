<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Listeners;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

/**
 * Lists tests that should be run in a separate process and throws an exception making the test suite fail.
 */
class SeparateProcessListener implements TestListener
{
    use TestListenerDefaultImplementation;

    private const PROBLEMATIC_CONSTANTS = [
        'MAUTIC_INTEGRATION_SYNC_IN_PROGRESS',
    ];

    /**
     * @var array<string,string[]>
     */
    private array $problematicTests = [];

    public function endTest(Test $test, float $time): void
    {
        if ($this->isTestRunInSeparateProcess($test)) {
            return;
        }

        $problematicConstants = $this->getDefinedProblematicConstants();

        if (!$problematicConstants) {
            return;
        }

        $this->trackProblematicTest($test, $problematicConstants);
    }

    /**
     * @param TestSuite|Test[] $suite
     */
    public function endTestSuite(TestSuite $suite): void
    {
        if (!$this->problematicTests) {
            return;
        }

        foreach ($this->problematicTests as $testName => $problematicConstants) {
            fwrite(STDOUT, sprintf('Test "%s" must be run in a separate process as there were defined the following constants during the test execution: "%s".%s', $testName, implode(', ', $problematicConstants), PHP_EOL));
        }

        throw new \LogicException('There are tests that must be run in a separate process!');
    }

    private function isTestRunInSeparateProcess(Test $test): bool
    {
        $reflection = new \ReflectionMethod($test, 'runInSeparateProcess');
        $reflection->setAccessible(true);

        return $reflection->invoke($test);
    }

    /**
     * @return string[]
     */
    private function getDefinedProblematicConstants(): array
    {
        $defined = get_defined_constants(true)['user'] ?? [];

        return array_intersect(array_keys($defined), self::PROBLEMATIC_CONSTANTS);
    }

    /**
     * @param string[] $problematicConstants
     */
    private function trackProblematicTest(Test $test, array $problematicConstants): void
    {
        if (!$test instanceof TestCase) {
            throw new \InvalidArgumentException(sprintf('$test must be an instance of "%s".', TestCase::class));
        }

        $testName = sprintf('%s::%s', $test::class, $test->getName());

        $this->problematicTests[$testName] = $problematicConstants;
    }
}
