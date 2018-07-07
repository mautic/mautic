<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\ContactSegmentFilters;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use Symfony\Component\DependencyInjection\Container;

class ContactSegmentFilterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterFactory
     */
    public function testLeadFilter()
    {
        $tableSchemaColumnsCache = $this->createMock(TableSchemaColumnsCache::class);
        $container               = $this->createMock(Container::class);
        $decoratorFactory        = $this->createMock(DecoratorFactory::class);

        $filterDecorator = $this->createMock(FilterDecoratorInterface::class);
        $decoratorFactory->expects($this->exactly(3))
            ->method('getDecoratorForFilter')
            ->willReturn($filterDecorator);

        $filterDecorator->expects($this->exactly(3))
            ->method('getQueryType')
            ->willReturn('MyQueryTypeId');

        $filterQueryBuilder = $this->createMock(FilterQueryBuilderInterface::class);
        $container->expects($this->exactly(3))
            ->method('get')
            ->with('MyQueryTypeId')
            ->willReturn($filterQueryBuilder);

        $contactSegmentFilterFactory = new ContactSegmentFilterFactory($tableSchemaColumnsCache, $container, $decoratorFactory);

        $leadList = new LeadList();
        $leadList->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'date_identified',
                'object'   => 'lead',
                'type'     => 'datetime',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
            [
                'glue'     => 'and',
                'type'     => 'text',
                'field'    => 'hit_url',
                'operator' => 'like',
                'filter'   => 'test.com',
                'display'  => '',
            ],
            [
                'glue'     => 'or',
                'type'     => 'lookup',
                'field'    => 'state',
                'operator' => '=',
                'filter'   => 'QLD',
                'display'  => '',
            ],
        ]);

        $contactSegmentFilters = $contactSegmentFilterFactory->getSegmentFilters($leadList);

        $this->assertInstanceOf(ContactSegmentFilters::class, $contactSegmentFilters);
        $this->assertCount(3, $contactSegmentFilters);
    }
}
