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
use Mautic\LeadBundle\Segment\QueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class LeadSegmentFilterFactory
{
    /**
     * @var LeadSegmentFilterDate
     */
    private $leadSegmentFilterDate;

    /**
     * @var LeadSegmentFilterOperator
     */
    private $leadSegmentFilterOperator;

    /** @var LeadSegmentFilterDescriptor */
    public $dictionary;

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    private $entityManager;

    public function __construct(LeadSegmentFilterDate $leadSegmentFilterDate, LeadSegmentFilterOperator $leadSegmentFilterOperator, LeadSegmentFilterDescriptor $dictionary, EntityManager $entityManager)
    {
        $this->leadSegmentFilterDate     = $leadSegmentFilterDate;
        $this->leadSegmentFilterOperator = $leadSegmentFilterOperator;
        $this->dictionary                = $dictionary;
        $this->entityManager             = $entityManager;
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
            $leadSegmentFilter = new LeadSegmentFilter($filter, $this->dictionary, $this->entityManager);

            $leadSegmentFilter->setQueryDescription($this->dictionary[$filter] ? $this->dictionary[$filter] : false);
            $leadSegmentFilter->setQueryBuilder($this->getQueryBuilderForFilter($leadSegmentFilter));
            $leadSegmentFilter->setDecorator($this->getDecoratorForFilter($leadSegmentFilter));

            //@todo replaced in query builder
            $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilter);
            $this->leadSegmentFilterDate->fixDateOptions($leadSegmentFilter);
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
        return new BaseFilterQueryBuilder();
    }

    /**
     * @param LeadSegmentFilter $filter
     *
     * @return BaseDecorator
     */
    protected function getDecoratorForFilter(LeadSegmentFilter $filter)
    {
        return new BaseDecorator();
    }
}
