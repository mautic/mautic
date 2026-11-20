<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit;

use Mautic\ApiBundle\Helper\EntityResultHelper;
use PHPUnit\Framework\TestCase;

// Smoke test that dg/bypass-finals is loaded by the test bootstrap,
// so final classes can be doubled in tests. Without it, doubling
// a final class throws and this test fails.
final class BypassFinalsTest extends TestCase
{
    public function testFinalClassCanBeMocked(): void
    {
        // createStub() throws for a final class unless BypassFinals is active
        $stub = $this->createStub(EntityResultHelper::class);
        $stub->method('getArray')->willReturn(['mocked']);

        $this->assertSame(['mocked'], $stub->getArray([]));
    }
}
