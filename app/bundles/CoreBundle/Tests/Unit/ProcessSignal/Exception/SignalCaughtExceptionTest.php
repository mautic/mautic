<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\ProcessSignal\Exception;

use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalState;
use PHPUnit\Framework\TestCase;

final class SignalCaughtExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new SignalCaughtException(15);
        $this->assertSame('Signal received: "15"', $exception->getMessage());
        $this->assertNotInstanceOf(ProcessSignalState::class, $exception->getState());
    }

    public function testGetState(): void
    {
        $state     = new ProcessSignalState(['key' => 'value']);
        $exception = new SignalCaughtException(15, $state);
        $this->assertSame($state, $exception->getState());
    }
}
