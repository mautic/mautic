<?php

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\ContactSegmentFilters;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContactSegmentFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Mautic\LeadBundle\Segment\ContactSegmentFilterFactory
     */
    public function testLeadFilter(): void
    {
        $tableSchemaColumnsCache = $this->createMock(TableSchemaColumnsCache::class);
        $container               = $this->createMock(Container::class);
        $decoratorFactory        = $this->createMock(DecoratorFactory::class);

        $filterDecorator = $this->createMock(FilterDecoratorInterface::class);
        $decoratorFactory->expects($this->exactly(4))
            ->method('getDecoratorForFilter')
            ->willReturn($filterDecorator);

        $filterDecorator->expects($this->exactly(4))
            ->method('getQueryType')
            ->willReturn('MyQueryTypeId');

        $filterQueryBuilder = $this->createMock(FilterQueryBuilderInterface::class);
        $container->expects($this->exactly(4))
            ->method('get')
            ->with('MyQueryTypeId')
            ->willReturn($filterQueryBuilder);

        $contactSegmentFilterFactory = new ContactSegmentFilterFactory($tableSchemaColumnsCache, $container, $decoratorFactory, $this->createMock(EventDispatcherInterface::class));

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
            [
                'glue'         => 'or',
                'type'         => 'lookup',
                'field'        => 'state',
                'operator'     => ContactSegmentFilterFactory::CUSTOM_OPERATOR,
                'properties'   => [
                    [
                        'operator' => '=',
                        'filter'   => 'QLD',
                    ],
                ],
                'merged_property' => [],
            ],
        ]);

        $contactSegmentFilters = $contactSegmentFilterFactory->getSegmentFilters($leadList);

        $this->assertInstanceOf(ContactSegmentFilters::class, $contactSegmentFilters);
        $this->assertCount(4, $contactSegmentFilters);
    }
}
