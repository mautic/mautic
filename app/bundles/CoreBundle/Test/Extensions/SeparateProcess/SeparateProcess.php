<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SeparateProcess;

use Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber\TestFinishedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber\TestPreparationFailedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber\TestPreparedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber\TestSuiteFinishedSubscriber;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class SeparateProcess implements Extension
{
    private bool $prepared          = false;
    private bool $preparationFailed = false;
    /**
     * @var array<string,string[]>
     */
    private array $problematicTests = [];

    private const PROBLEMATIC_CONSTANTS = [
        'MAUTIC_INTEGRATION_SYNC_IN_PROGRESS',
    ];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->registerSubscribers($facade);
    }

    public function testPreparationFailed(): void
    {
        $this->preparationFailed = true;
    }

    public function testPrepared(): void
    {
        $this->prepared = true;
    }

    public function testFinished(Finished $event): void
    {
        if (!$this->prepared || $this->preparationFailed) {
            return;
        }

        $this->prepared          = false;
        $this->preparationFailed = false;

        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        assert($test instanceof TestMethod);

        if ($test->metadata()->isRunInSeparateProcess()->isNotEmpty()) {
            return;
        }

        $problematicConstants = $this->getDefinedProblematicConstants();

        if ([] === $problematicConstants) {
            return;
        }

        $this->trackProblematicTest($test, $problematicConstants);
    }

    public function testSuiteFinished(\PHPUnit\Event\TestSuite\Finished $finished): void
    {
        if ([] === $this->problematicTests) {
            return;
        }

        foreach ($this->problematicTests as $testName => $problematicConstants) {
            fwrite(STDOUT, sprintf('Test "%s" must be run in a separate process as there were defined the following constants during the test execution: "%s".%s', $testName, implode(', ', $problematicConstants), PHP_EOL));
        }

        throw new \LogicException('There are tests that must be run in a separate process!');
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
    private function trackProblematicTest(TestMethod $test, array $problematicConstants): void
    {
        $testName = $test->nameWithClass();

        $this->problematicTests[$testName] = $problematicConstants;
    }

    private function registerSubscribers(Facade $facade): void
    {
        $facade->registerSubscribers(
            new TestFinishedSubscriber($this),
            new TestPreparationFailedSubscriber($this),
            new TestPreparedSubscriber($this),
            new TestSuiteFinishedSubscriber($this),
        );
    }
}
