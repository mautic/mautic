<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\ProcessSignal\Exception;

use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalState;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class SignalCaughtExceptionTest extends TestCase
{
    public function testGetMessage(): void
    {
        $exception = new SignalCaughtException(15);
        Assert::assertSame('Signal received: "15"', $exception->getMessage());
        Assert::assertNull($exception->getState());
    }

    public function testGetState(): void
    {
        $state     = new ProcessSignalState(['key' => 'value']);
        $exception = new SignalCaughtException(15, $state);
        Assert::assertSame($state, $exception->getState());
    }
}
