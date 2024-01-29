<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\EventListener\SegmentLogReportSubscriber;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use PHPUnit\Framework\TestCase;

class SegmentLogReportSubscriberTest extends TestCase
{
    /**
     * @var FieldsBuilder
     */
    private \PHPUnit\Framework\MockObject\MockObject $fieldsBuilder;

    private \Mautic\LeadBundle\EventListener\SegmentLogReportSubscriber $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->fieldsBuilder = $this->createMock(FieldsBuilder::class);

        $this->subscriber = new SegmentLogReportSubscriber(
            $this->fieldsBuilder
        );
    }

    public function testOnReportBuilder(): void
    {
        $mockEvent = $this->getMockBuilder(ReportBuilderEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'checkContext',
                'addTable',
            ])
            ->getMock();

        $mockEvent->expects($this->exactly(1))
            ->method('checkContext')
            ->willReturn(true);

        $this->fieldsBuilder->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->willReturn([]);

        $this->fieldsBuilder->expects($this->once())
            ->method('getLeadFilter')
            ->willReturn([
                'log_added.leadlist_id' => [],
            ]);

        $setTables = [];
        $mockEvent->expects($this->exactly(1))
            ->method('addTable')
            ->willReturnCallback(function () use (&$setTables): void {
                $args = func_get_args();

                $setTables[] = $args;
            });

        $this->subscriber->onReportBuilder($mockEvent);
        $this->assertCount(1, $setTables);
    }

    public function testOnReportGenerate(): void
    {
        // Mock query builder
        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'andWhere', 'leftJoin', 'expr', 'setParameter', 'groupBy'])
            ->getMock();

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects($this->exactly(1))
            ->method('or')
            ->willReturn($this->createMock(CompositeExpression::class));
        $expressionBuilder->expects($this->exactly(2))
            ->method('isNotNull')
            ->willReturn('');

        $mockQueryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->exactly(3))
            ->method('leftJoin')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->exactly(3))
            ->method('expr')
            ->willReturn($expressionBuilder);

        $mockQueryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->exactly(1))
            ->method('groupBy')
            ->willReturn($mockQueryBuilder);

        // Mock event
        $mockEvent = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'checkContext',
                'getQueryBuilder',
                'getOptions',
                'hasGroupBy',
                'hasColumn',
                'hasFilter',
                'setQueryBuilder',
                'addLeadIpAddressLeftJoin',
            ])
            ->getMock();

        $mockEvent->expects($this->exactly(1))
            ->method('checkContext')
            ->willReturn(true);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->exactly(2))
            ->method('getOptions')
            ->willReturn([
                'dateFrom' => new \DateTime(),
                'dateTo'   => new \DateTime(),
            ]);

        $mockEvent->expects($this->exactly(1))
            ->method('hasGroupBy')
            ->willReturn(false);

        $mockEvent->expects($this->exactly(2))
            ->method('hasColumn')
            ->willReturn(true);

        $mockEvent->expects($this->exactly(0))
            ->method('hasFilter')
            ->willReturn(true);

        $mockEvent->expects($this->exactly(1))
            ->method('addLeadIpAddressLeftJoin')
            ->willReturn($mockEvent);

        $this->subscriber->onReportGenerate($mockEvent);
    }
}
