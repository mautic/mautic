<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\ProcessSignal;

use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;

class ProcessSignalService
{
    public const SIGTERM  = 15;
    public const SIGINT   = 2;
    private const SIGNALS = [self::SIGTERM, self::SIGINT];
    private ?int $signal  = null;

    /**
     * @param int[] $signals
     */
    public function registerSignalHandler(callable $beforeCallback = null, array $signals = self::SIGNALS): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        $handler = function (int $signal) use ($beforeCallback): void {
            if ($beforeCallback) {
                call_user_func($beforeCallback, $signal);
            }

            $this->signal = $signal;
        };

        foreach ($signals as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    /**
     * @param int[] $signals
     */
    public function restoreSignalHandler(array $signals = self::SIGNALS): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        foreach ($signals as $signal) {
            pcntl_signal($signal, SIG_DFL);
        }
    }

    public function isSignalCaught(): bool
    {
        if (!function_exists('pcntl_signal_dispatch')) {
            return false;
        }

        pcntl_signal_dispatch();

        return null !== $this->signal;
    }

    /**
     * @throws SignalCaughtException
     */
    public function throwExceptionIfSignalIsCaught(): void
    {
        if (!$this->isSignalCaught()) {
            return;
        }

        throw new SignalCaughtException($this->signal);
    }
}
