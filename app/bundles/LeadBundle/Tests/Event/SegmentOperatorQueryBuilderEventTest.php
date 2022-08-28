<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

final class SegmentOperatorQueryBuilderEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MockObject|ContactSegmentFilter
     */
    private $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->filter       = $this->createMock(ContactSegmentFilter::class);

        $this->queryBuilder->method('getTableAlias')
            ->with(MAUTIC_TABLE_PREFIX.'leads')
            ->willReturn('leads');
    }

    public function testConstructGettersSetters(): void
    {
        $this->filter->method('getOperator')->willReturn('=');
        $this->filter->method('getGlue')->willReturn('and');

        $event = new SegmentOperatorQueryBuilderEvent($this->queryBuilder, $this->filter, 'parameterHolder1');

        $this->assertSame($this->queryBuilder, $event->getQueryBuilder());
        $this->assertSame($this->filter, $event->getFilter());
        $this->assertSame('parameterHolder1', $event->getParameterHolder());
        $this->assertFalse($event->operatorIsOneOf('like'));
        $this->assertTrue($event->operatorIsOneOf('=', 'like'));
        $this->assertFalse($event->wasOperatorHandled());

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with('a != b', 'and');

        $event->addExpression('a != b');

        $this->assertTrue($event->wasOperatorHandled());
    }
}
