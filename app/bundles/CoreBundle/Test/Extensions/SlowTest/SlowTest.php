<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest;

use Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber\TestFinishedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber\TestPreparationFailedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber\TestPreparationStartedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber\TestPreparedSubscriber;
use Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber\TestSuiteFinishedSubscriber;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class SlowTest implements Extension
{
    private bool $prepared          = false;
    private bool $preparationFailed = false;

    /**
     * @var array<string, float>
     */
    private array $classes = [];

    private bool $enabled;

    private float $threshold;

    private ?HRTime $time = null;

    public function __construct()
    {
        $this->enabled   = (bool) getenv('MAUTIC_TEST_LOG_SLOW_TESTS');
        $this->threshold = (float) (getenv('MAUTIC_TEST_SLOW_TESTS_THRESHOLD') ?: 2);
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if (!$this->enabled) {
            return;
        }

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

    public function testPreparationStarted(PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        $this->time = $event->telemetryInfo()->time();
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

        $this->handleFinish($event->telemetryInfo(), $test);

        $this->time = null;
    }

    public function testSuiteFinished(\PHPUnit\Event\TestSuite\Finished $finished): void
    {
        if ([] === $this->classes) {
            return;
        }

        arsort($this->classes);

        fwrite(STDOUT, PHP_EOL.'Slow test classes:'.PHP_EOL.var_export($this->classes, true).PHP_EOL);

        $this->classes = [];
    }

    private function handleFinish(Info $telemetryInfo, TestMethod $test): void
    {
        assert(null !== $this->time);

        $time = $telemetryInfo->time()->duration($this->time)->asFloat();

        if ($time <= $this->threshold) {
            return;
        }

        $class = $test->className();

        if (!isset($this->classes[$class])) {
            $this->classes[$class] = 0.0;
        }

        $this->classes[$class] += $time;
    }

    private function registerSubscribers(Facade $facade): void
    {
        $facade->registerSubscribers(
            new TestFinishedSubscriber($this),
            new TestPreparationFailedSubscriber($this),
            new TestPreparationStartedSubscriber($this),
            new TestPreparedSubscriber($this),
            new TestSuiteFinishedSubscriber($this),
        );
    }
}
