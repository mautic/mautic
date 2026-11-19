<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\Query\Filter\Exception\UnsupportedFilterOperatorException;
use PHPUnit\Framework\TestCase;

final class UnsupportedFilterOperatorExceptionTest extends TestCase
{
    public function testFromOperatorBuildsUnsupportedOperatorException(): void
    {
        $exception = UnsupportedFilterOperatorException::fromOperator('unsupported');

        $this->assertSame('Unsupported filter operator "unsupported".', $exception->getMessage());
    }
}
