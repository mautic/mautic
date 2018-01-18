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

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;
use Symfony\Component\DependencyInjection\Container;

class LeadSegmentFilterFactory
{
    /**
     * @var LeadSegmentFilterDate
     */
    private $leadSegmentFilterDate;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private $entityManager;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var LeadSegmentFilterDescriptor
     */
    private $leadSegmentFilterDescriptor;

    /**
     * @var BaseDecorator
     */
    private $baseDecorator;

    /**
     * @var CustomMappedDecorator
     */
    private $customMappedDecorator;

    public function __construct(
        LeadSegmentFilterDate $leadSegmentFilterDate,
        EntityManager $entityManager,
        Container $container,
        LeadSegmentFilterDescriptor $leadSegmentFilterDescriptor,
        BaseDecorator $baseDecorator,
        CustomMappedDecorator $customMappedDecorator
    ) {
        $this->leadSegmentFilterDate       = $leadSegmentFilterDate;
        $this->entityManager               = $entityManager;
        $this->container                   = $container;
        $this->leadSegmentFilterDescriptor = $leadSegmentFilterDescriptor;
        $this->baseDecorator               = $baseDecorator;
        $this->customMappedDecorator       = $customMappedDecorator;
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

            $decorator = $this->getDecoratorForFilter($leadSegmentFilterCrate);

            $leadSegmentFilter = new LeadSegmentFilter($leadSegmentFilterCrate, $decorator, $this->entityManager);
            //$this->leadSegmentFilterDate->fixDateOptions($leadSegmentFilter);
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

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return FilterDecoratorInterface
     */
    protected function getDecoratorForFilter(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField])) {
            return $this->baseDecorator;
        }

        return $this->customMappedDecorator;
    }
}
