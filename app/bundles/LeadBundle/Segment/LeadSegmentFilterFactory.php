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

    /** @var LeadSegmentFilterDescriptor  */
    private $dictionary;

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    private $schema;

    public function __construct(
        LeadSegmentFilterDate $leadSegmentFilterDate,
        LeadSegmentFilterOperator $leadSegmentFilterOperator,
        LeadSegmentFilterDescriptor $dictionary,
        EntityManager $entityManager
)
    {
        $this->leadSegmentFilterDate     = $leadSegmentFilterDate;
        $this->leadSegmentFilterOperator = $leadSegmentFilterOperator;
        $this->dictionary                = $dictionary;
        $this->schema                    = $entityManager->getConnection()->getSchemaManager();
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
            $leadSegmentFilter = new LeadSegmentFilter($filter, $this->dictionary, $this->schema);
            $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilter);
            $this->leadSegmentFilterDate->fixDateOptions($leadSegmentFilter);
            $leadSegmentFilters->addLeadSegmentFilter($leadSegmentFilter);
        }

        return $leadSegmentFilters;
    }
}
