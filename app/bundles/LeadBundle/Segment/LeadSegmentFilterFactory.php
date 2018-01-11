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
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\QueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class LeadSegmentFilterFactory
{
    /**
     * @var LeadSegmentFilterDate
     */
    private $leadSegmentFilterDate;

    /**
     * @var LeadSegmentFilterDescriptor
     */
    public $dictionary;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private $entityManager;

    /**
     * @var BaseDecorator
     */
    private $baseDecorator;

    public function __construct(
        LeadSegmentFilterDate $leadSegmentFilterDate,
        LeadSegmentFilterDescriptor $dictionary,
        EntityManager $entityManager,
        BaseDecorator $baseDecorator
    ) {
        $this->leadSegmentFilterDate = $leadSegmentFilterDate;
        $this->dictionary            = $dictionary;
        $this->entityManager         = $entityManager;
        $this->baseDecorator         = $baseDecorator;
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

            $leadSegmentFilter = new LeadSegmentFilter($leadSegmentFilterCrate, $decorator, $this->dictionary, $this->entityManager);
            //$this->leadSegmentFilterDate->fixDateOptions($leadSegmentFilter);
            $leadSegmentFilter->setQueryDescription(
                isset($this->dictionary[$leadSegmentFilter->getField()]) ? $this->dictionary[$leadSegmentFilter->getField()] : false
            );
            $leadSegmentFilter->setQueryBuilder($this->getQueryBuilderForFilter($leadSegmentFilter));
            //dump($leadSegmentFilter);
            //dump($leadSegmentFilter->getOperator());
            //continue;

            //@todo replaced in query builder
            $leadSegmentFilters->addLeadSegmentFilter($leadSegmentFilter);
        }
        //die();
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
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return FilterDecoratorInterface
     */
    protected function getDecoratorForFilter(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->baseDecorator;
    }
}
