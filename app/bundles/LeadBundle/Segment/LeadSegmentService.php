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
use Mautic\LeadBundle\Entity\LeadListSegmentRepository;

class LeadSegmentService
{
    /**
     * @var LeadListSegmentRepository
     */
    private $leadListSegmentRepository;

    /**
     * @var LeadSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    public function __construct(LeadSegmentFilterFactory $leadSegmentFilterFactory, LeadListSegmentRepository $leadListSegmentRepository)
    {
        $this->leadListSegmentRepository = $leadListSegmentRepository;
        $this->leadSegmentFilterFactory  = $leadSegmentFilterFactory;
    }

    public function getNewLeadsByListCount(LeadList $entity, array $batchLimiters)
    {
        $segmentFilters = $this->leadSegmentFilterFactory->getLeadListFilters($entity);

        return $this->leadListSegmentRepository->getNewLeadsByListCount($entity->getId(), $segmentFilters, $batchLimiters);
    }
}
