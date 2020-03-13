<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

class SegmentOperatorQueryBuilderEventTest extends \PHPUnit\Framework\TestCase
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
