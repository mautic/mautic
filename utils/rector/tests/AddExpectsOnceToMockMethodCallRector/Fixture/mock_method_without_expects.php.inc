<?php

namespace Utils\Rector\Tests\AddExpectsOnceToMockMethodCallRector\Fixture;

use PHPUnit\Framework\TestCase;

class MockMethodWithoutExpects extends TestCase
{
    public function testPayload()
    {
        $queueMock = $this->createMock(\stdClass::class);
        $queueMock->method('getPayload')->willReturn('{"the": "payload"}');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\AddExpectsOnceToMockMethodCallRector\Fixture;

use PHPUnit\Framework\TestCase;

class MockMethodWithoutExpects extends TestCase
{
    public function testPayload()
    {
        $queueMock = $this->createMock(\stdClass::class);
        $queueMock->expects($this->once())->method('getPayload')->willReturn('{"the": "payload"}');
    }
}

?>
