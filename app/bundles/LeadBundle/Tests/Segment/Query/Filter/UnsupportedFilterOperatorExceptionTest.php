<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\Query\Filter\Exception\UnsupportedFilterOperatorException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class UnsupportedFilterOperatorExceptionTest extends TestCase
{
    public function testFromOperatorBuildsUnsupportedOperatorException(): void
    {
        $exception = UnsupportedFilterOperatorException::fromOperator('unsupported');

        Assert::assertSame('Unsupported filter operator "unsupported".', $exception->getMessage());
    }
}
