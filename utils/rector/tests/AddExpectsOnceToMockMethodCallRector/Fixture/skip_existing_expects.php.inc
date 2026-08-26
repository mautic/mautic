<?php

namespace Utils\Rector\Tests\AddExpectsOnceToMockMethodCallRector\Fixture;

use PHPUnit\Framework\TestCase;

class SkipExistingExpects extends TestCase
{
    public function testPayload()
    {
        $queueMock = $this->createMock(\stdClass::class);
        $queueMock->expects($this->never())->method('getPayload');
    }
}
