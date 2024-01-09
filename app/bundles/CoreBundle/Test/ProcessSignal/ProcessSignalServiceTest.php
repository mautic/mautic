<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\ProcessSignal;

use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ProcessSignalServiceTest extends TestCase
{
    private ProcessSignalService $processSignalService;

    protected function setUp(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('PCNTL extension is required.');
        }

        if (!function_exists('posix_kill')) {
            $this->markTestSkipped('POSIX extension is required.');
        }

        $this->processSignalService = new ProcessSignalService();
    }

    protected function tearDown(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGUSR1, SIG_DFL);
        pcntl_signal(SIGUSR2, SIG_DFL);
    }

    /**
     * @return iterable<string, array{int, int[]}>
     */
    public function dataSignals(): iterable
    {
        yield 'SIGUSR1' => [SIGUSR1, [SIGUSR1, SIGUSR2]];
        yield 'SIGUSR2' => [SIGUSR2, [SIGUSR1, SIGUSR2]];
    }

    /**
     * @dataProvider dataSignals
     *
     * @param int[] $signals
     */
    public function testRegisterSignalHandler(int $signal, array $signals): void
    {
        $beforeCallbackCalled = false;

        $this->processSignalService->registerSignalHandler(function () use (&$beforeCallbackCalled) {
            $beforeCallbackCalled = true;
        }, $signals);

        posix_kill(posix_getpid(), $signal);

        Assert::assertTrue($this->processSignalService->isSignalCaught());
        Assert::assertTrue($beforeCallbackCalled);
    }

    /**
     * @dataProvider dataSignals
     *
     * @param int[] $signals
     */
    public function testRestoreSignalHandler(int $signal, array $signals): void
    {
        $this->processSignalService->registerSignalHandler(null, $signals);
        Assert::assertIsCallable(pcntl_signal_get_handler($signal));

        $this->processSignalService->restoreSignalHandler($signals);
        Assert::assertSame(SIG_DFL, pcntl_signal_get_handler($signal));
    }

    /**
     * @dataProvider dataSignals
     *
     * @param int[] $signals
     */
    public function testIsSignalCaught(int $signal, array $signals): void
    {
        Assert::assertFalse($this->processSignalService->isSignalCaught());

        $this->processSignalService->registerSignalHandler(null, $signals);

        posix_kill(posix_getpid(), $signal);

        Assert::assertTrue($this->processSignalService->isSignalCaught());
    }

    /**
     * @dataProvider dataSignals
     *
     * @param int[] $signals
     */
    public function testThrowExceptionIfSignalIsCaught(int $signal, array $signals): void
    {
        $this->processSignalService->registerSignalHandler(null, $signals);

        posix_kill(posix_getpid(), $signal);

        $this->expectException(SignalCaughtException::class);
        $this->expectExceptionCode($signal);
        $this->expectExceptionMessage(sprintf('Signal received: "%d"', $signal));

        $this->processSignalService->throwExceptionIfSignalIsCaught();
    }
}
