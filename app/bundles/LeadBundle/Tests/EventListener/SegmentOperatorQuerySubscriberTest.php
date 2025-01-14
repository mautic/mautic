<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\EventListener\SegmentOperatorQuerySubscriber;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SegmentOperatorQuerySubscriberTest extends TestCase
{
    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MockObject|ExpressionBuilder
     */
    private $expressionBuilder;

    /**
     * @var MockObject|ContactSegmentFilter
     */
    private $contactSegmentFilter;

    /**
     * @var SegmentOperatorQuerySubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->expressionBuilder    = $this->createMock(ExpressionBuilder::class);
        $this->contactSegmentFilter = $this->createMock(ContactSegmentFilter::class);
        $this->subscriber           = new SegmentOperatorQuerySubscriber();

        $this->queryBuilder->method('expr')->willReturn($this->expressionBuilder);
        $this->queryBuilder->method('getTableAlias')->willReturn('l');
    }

    public function testOnEmptyOperatorIfNotEmpty(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('unicorn');

        $this->queryBuilder->expects($this->never())
            ->method('addLogic');

        $this->subscriber->onEmptyOperator($event);

        $this->assertFalse($event->wasOperatorHandled());
    }

    /**
     * @dataProvider dataOnEmptyOperatorIfEmpty
     */
    public function testOnEmptyOperatorIfEmpty(bool $doesColumnSupportEmptyValue, string $expectedExpression): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getField')
            ->willReturn('email');

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('empty');

        $this->contactSegmentFilter->method('getGlue')
            ->willReturn(CompositeExpression::TYPE_AND);

        $this->contactSegmentFilter->method('doesColumnSupportEmptyValue')
            ->willReturn($doesColumnSupportEmptyValue);

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with(
                $this->callback(function (CompositeExpression $expression) use ($expectedExpression) {
                    $this->assertSame($expectedExpression, (string) $expression);

                    return true;
                }),
                CompositeExpression::TYPE_AND
            );

        $this->expressionBuilder->expects($this->once())
            ->method('isNull')
            ->with('l.email')
            ->willReturnCallback(function ($x) {
                return $x.' IS NULL';
            });

        $this->expressionBuilder->expects($doesColumnSupportEmptyValue ? $this->once() : $this->never())
            ->method('eq')
            ->with('l.email')
            ->willReturnCallback(function ($x, $y) {
                return $x.' = '.$y;
            });

        $this->expressionBuilder->expects($doesColumnSupportEmptyValue ? $this->once() : $this->never())
            ->method('literal')
            ->with('')
            ->willReturn("''");

        $this->subscriber->onEmptyOperator($event);

        $this->assertTrue($event->wasOperatorHandled());
    }

    /**
     * @return iterable<array<bool|string>>
     */
    public function dataOnEmptyOperatorIfEmpty(): iterable
    {
        yield [false, 'l.email IS NULL'];
        yield [true, "(l.email IS NULL) OR (l.email = '')"];
    }

    public function testOnNotEmptyOperatorIfNotEmpty(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('unicorn');

        $this->queryBuilder->expects($this->never())
            ->method('addLogic');

        $this->subscriber->onNotEmptyOperator($event);

        $this->assertFalse($event->wasOperatorHandled());
    }

    /**
     * @dataProvider dataOnNotEmptyOperatorIfEmpty
     */
    public function testOnNotEmptyOperatorIfEmpty(bool $doesColumnSupportEmptyValue, string $expectedExpression): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getField')
            ->willReturn('email');

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('notEmpty');

        $this->contactSegmentFilter->method('getGlue')
            ->willReturn(CompositeExpression::TYPE_AND);

        $this->contactSegmentFilter->method('doesColumnSupportEmptyValue')
            ->willReturn($doesColumnSupportEmptyValue);

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with(
                $this->callback(function (CompositeExpression $expression) use ($expectedExpression) {
                    $this->assertSame($expectedExpression, (string) $expression);

                    return true;
                }),
                CompositeExpression::TYPE_AND
            );

        $this->expressionBuilder->expects($this->once())
            ->method('isNotNull')
            ->with('l.email')
            ->willReturnCallback(function ($x) {
                return $x.' IS NOT NULL';
            });

        $this->expressionBuilder->expects($doesColumnSupportEmptyValue ? $this->once() : $this->never())
            ->method('neq')
            ->with('l.email')
            ->willReturnCallback(function ($x, $y) {
                return $x.' <> '.$y;
            });

        $this->expressionBuilder->expects($doesColumnSupportEmptyValue ? $this->once() : $this->never())
            ->method('literal')
            ->with('')
            ->willReturn("''");

        $this->subscriber->onNotEmptyOperator($event);

        $this->assertTrue($event->wasOperatorHandled());
    }

    /**
     * @return iterable<array<bool|string>>
     */
    public function dataOnNotEmptyOperatorIfEmpty(): iterable
    {
        yield [false, 'l.email IS NOT NULL'];
        yield [true, "(l.email IS NOT NULL) AND (l.email <> '')"];
    }

    public function testOnNegativeOperatorsIfNotNegativeOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('unicorn');

        $this->expressionBuilder->expects($this->never())
            ->method('isNull');

        $this->subscriber->onNegativeOperators($event);

        $this->assertFalse($event->wasOperatorHandled());
    }

    public function testOnNegativeOperatorsIfNegativeOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getField')
            ->willReturn('email');

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('notBetween');

        $this->contactSegmentFilter->method('getGlue')
            ->willReturn(CompositeExpression::TYPE_AND);

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with(
                $this->anything(),
                CompositeExpression::TYPE_AND
            );

        $this->expressionBuilder->expects($this->once())
            ->method('orX');

        $this->expressionBuilder->expects($this->once())
            ->method('isNull')
            ->with('l.email');

        $this->expressionBuilder->expects($this->once())
            ->method('notBetween')
            ->with('l.email', 'paramenter_holder_1');

        $this->subscriber->onNegativeOperators($event);

        $this->assertTrue($event->wasOperatorHandled());
    }

    public function testOnMultiselectOperatorsIfNotMultiselectOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            ['paramenter_holder_1']
        );

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('unicorn');

        $this->subscriber->onMultiselectOperators($event);

        $this->assertFalse($event->wasOperatorHandled());
    }

    public function testOnMultiselectOperatorsIfMultiselectOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            ['paramenter_holder_1']
        );

        $this->contactSegmentFilter->method('getField')
            ->willReturn('email');

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('multiselect');

        $this->contactSegmentFilter->method('getGlue')
            ->willReturn(CompositeExpression::TYPE_AND);

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with(
                $this->anything(),
                CompositeExpression::TYPE_AND
            );

        $this->expressionBuilder->expects($this->once())
            ->method('andX');

        $this->expressionBuilder->expects($this->once())
            ->method('regexp')
            ->with('l.email', 'paramenter_holder_1');

        $this->subscriber->onMultiselectOperators($event);

        $this->assertTrue($event->wasOperatorHandled());
    }

    public function testOnDefaultOperatorsIfNotDefaultOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('unicorn');

        $this->subscriber->onDefaultOperators($event);

        $this->assertFalse($event->wasOperatorHandled());
    }

    public function testOnDefaultOperatorsIfDefaultOperator(): void
    {
        $event = new SegmentOperatorQueryBuilderEvent(
            $this->queryBuilder,
            $this->contactSegmentFilter,
            'paramenter_holder_1'
        );

        $this->contactSegmentFilter->method('getField')
            ->willReturn('email');

        $this->contactSegmentFilter->method('getOperator')
            ->willReturn('gt');

        $this->contactSegmentFilter->method('getGlue')
            ->willReturn(CompositeExpression::TYPE_AND);

        $this->queryBuilder->expects($this->once())
            ->method('addLogic')
            ->with(
                $this->anything(),
                CompositeExpression::TYPE_AND
            );

        $this->expressionBuilder->expects($this->once())
            ->method('gt')
            ->with('l.email', 'paramenter_holder_1');

        $this->subscriber->onDefaultOperators($event);

        $this->assertTrue($event->wasOperatorHandled());
    }
}
