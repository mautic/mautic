<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\MockReturn;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SkipMatchingMockReturn extends TestCase
{
    private MockObject $genericMock;

    public function test(): void
    {
        $someMock = $this->createMock(SomeMockedService::class);
        $someMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $someMock->method('getName')
            ->willReturn('name');

        $someMock->method('getNullableName')
            ->willReturn(null);

        $someMock->method('getSelf')
            ->willReturn($this->createMock(SomeMockedService::class));

        $someMock->method('getSelf')
            ->willReturn($this->genericMock);

        $someMock->method('setName')
            ->willReturn($someMock);

        $someMock->method('getUntyped')
            ->willReturn('anything');
    }
}
