<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\ProcessSignal;

use Mautic\CoreBundle\ProcessSignal\Exception\InvalidStateException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalState;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProcessSignalTest extends TestCase
{
    public function testGetData(): void
    {
        $data  = ['key' => 'value'];
        $state = new ProcessSignalState($data);

        Assert::assertSame($data, $state->getData());
    }

    public function testToString(): void
    {
        $data  = ['key' => 'value'];
        $state = new ProcessSignalState($data);

        Assert::assertSame('<<<StartOfState>>>{"key":"value"}<<<EndOfState>>>', (string) $state);
    }

    /**
     * @return iterable<string, string[]>
     */
    public static function dataFromStringThrowsException(): iterable
    {
        yield 'No tag' => ['No tag'];
        yield 'Invalid tag' => ['<<<StartOfState>>{"key":"value"}<<<EndOfState>>>'];
        yield 'Invalid JSON' => ['<<<StartOfState>>>{"key"="value"}<<<EndOfState>>>'];
    }

    #[DataProvider('datafromStringThrowsException')]
    public function testFromStringThrowsException(string $string): void
    {
        $this->expectException(InvalidStateException::class);
        ProcessSignalState::fromString($string);
    }
}
