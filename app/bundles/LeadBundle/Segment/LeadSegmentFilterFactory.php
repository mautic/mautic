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

    public function __construct(LeadSegmentFilterDate $leadSegmentFilterDate, LeadSegmentFilterOperator $leadSegmentFilterOperator)
    {
        $this->leadSegmentFilterDate     = $leadSegmentFilterDate;
        $this->leadSegmentFilterOperator = $leadSegmentFilterOperator;
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
            $leadSegmentFilter = new LeadSegmentFilter($filter);
            $this->leadSegmentFilterOperator->fixOperator($leadSegmentFilter);
            $this->leadSegmentFilterDate->fixDateOptions($leadSegmentFilter);
            $leadSegmentFilters->addLeadSegmentFilter($leadSegmentFilter);
        }

        return $leadSegmentFilters;
    }
}
