<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\MockReturn;

use PHPUnit\Framework\TestCase;

final class MismatchedMockReturn extends TestCase
{
    public function test(): void
    {
        $someMock = $this->createMock(SomeMockedService::class);
        $someMock->expects($this->once())
            ->method('getItems')
            ->willReturn('');

        $someMock->method('getName')
            ->willReturn(['name']);

        $someMock->method('getNullableName')
            ->willReturn('first', 100);
    }
}
