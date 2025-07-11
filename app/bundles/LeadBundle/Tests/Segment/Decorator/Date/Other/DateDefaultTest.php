<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateDefault;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;

#[\PHPUnit\Framework\Attributes\CoversClass(DateDefault::class)]
class DateDefaultTest extends \PHPUnit\Framework\TestCase
{
    public function testGetParameterValue(): void
    {
        $dateDecorator             = $this->createMock(DateDecorator::class);
        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $filterDecorator = new DateDefault($dateDecorator, '2018-03-02 01:02:03');

        $this->assertEquals('2018-03-02 01:02:03', $filterDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetWhereReturnsCompositeExpression(): void
    {
        $dateDecorator = $this->createMock(DateDecorator::class);
        $filterCrate   = new ContactSegmentFilterCrate(['field' => 'last_active']);

        // Configure DateDecorator mock to return CompositeExpression
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->with($filterCrate)
            ->willReturn(CompositeExpression::and('field = 1', 'field = 2'));

        $filterDecorator = new DateDefault($dateDecorator, '2025-01-01');

        $this->assertInstanceOf(
            CompositeExpression::class,
            $filterDecorator->getWhere($filterCrate)
        );
    }

    public function testGetWhereReturnsString(): void
    {
        $dateDecorator = $this->createMock(DateDecorator::class);
        $filterCrate   = new ContactSegmentFilterCrate(['field' => 'last_active']);

        // Configure to return string
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn("date_field > '2025-01-01'");

        $filterDecorator = new DateDefault($dateDecorator, '2025-01-01');

        $this->assertSame(
            "date_field > '2025-01-01'",
            $filterDecorator->getWhere($filterCrate)
        );
    }

    public function testGetWhereReturnsNull(): void
    {
        $dateDecorator = $this->createMock(DateDecorator::class);
        $filterCrate   = new ContactSegmentFilterCrate(['field' => 'last_active']);

        // Configure to return null
        $dateDecorator->expects($this->once())
            ->method('getWhere')
            ->willReturn(null);

        $filterDecorator = new DateDefault($dateDecorator, '2025-01-01');

        $this->assertNull($filterDecorator->getWhere($filterCrate));
    }
}
