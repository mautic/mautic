<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class PointsChangeEvent.
 */
class LeadUtmTagsEvent extends CommonEvent
{
    /**
     * @param bool $utmtags
     */
    public function __construct(Lead $lead, protected $utmtags)
    {
        $this->entity  = $lead;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Returns the new points.
     *
     * @return int
     */
    public function getUtmTags()
    {
        return $this->utmtags;
    }
}
