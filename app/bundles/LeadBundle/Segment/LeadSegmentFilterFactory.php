<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Container;

class LeadSegmentFilterFactory
{
    /**
     * @var TableSchemaColumnsCache
     */
    private $schemaCache;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var DecoratorFactory
     */
    private $decoratorFactory;

    public function __construct(
        TableSchemaColumnsCache $schemaCache,
        Container $container,
        DecoratorFactory $decoratorFactory
    ) {
        $this->schemaCache      = $schemaCache;
        $this->container        = $container;
        $this->decoratorFactory = $decoratorFactory;
    }

    /**
     * @param LeadList $leadList
     *
     * @return LeadSegmentFilters
     */
    public function getLeadListFilters(LeadList $leadList)
    {
        $leadSegmentFilters = new LeadSegmentFilters();

        $filters = $leadList->getFilters();
        foreach ($filters as $filter) {
            // LeadSegmentFilterCrate is for accessing $filter as an object
            $leadSegmentFilterCrate = new LeadSegmentFilterCrate($filter);

            $decorator = $this->decoratorFactory->getDecoratorForFilter($leadSegmentFilterCrate);

            $filterQueryBuilder = $this->getQueryBuilderForFilter($decorator, $leadSegmentFilterCrate);

            $leadSegmentFilter = new LeadSegmentFilter($leadSegmentFilterCrate, $decorator, $this->schemaCache, $filterQueryBuilder);

            $leadSegmentFilters->addLeadSegmentFilter($leadSegmentFilter);
        }

        return $leadSegmentFilters;
    }

    /**
     * @param FilterDecoratorInterface $decorator
     * @param LeadSegmentFilterCrate   $leadSegmentFilterCrate
     *
     * @return FilterQueryBuilderInterface
     */
    private function getQueryBuilderForFilter(FilterDecoratorInterface $decorator, LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $qbServiceId = $decorator->getQueryType($leadSegmentFilterCrate);

        return $this->container->get($qbServiceId);
    }
}
