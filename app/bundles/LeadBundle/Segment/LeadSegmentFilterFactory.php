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

            $leadSegmentFilter = new LeadSegmentFilter($leadSegmentFilterCrate, $decorator, $this->schemaCache);

            $leadSegmentFilter->setFilterQueryBuilder($this->getQueryBuilderForFilter($leadSegmentFilter));

            //@todo replaced in query builder
            $leadSegmentFilters->addLeadSegmentFilter($leadSegmentFilter);
        }

        return $leadSegmentFilters;
    }

    /**
     * @param LeadSegmentFilter $filter
     *
     * @return BaseFilterQueryBuilder
     */
    protected function getQueryBuilderForFilter(LeadSegmentFilter $filter)
    {
        $qbServiceId = $filter->getQueryType();

        return $this->container->get($qbServiceId);
    }
}
