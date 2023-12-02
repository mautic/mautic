<?php

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;

class PointActionEvent extends CommonEvent
{
    protected \Mautic\PointBundle\Entity\Point $point;

    protected \Mautic\LeadBundle\Entity\Lead $lead;

    public function __construct(Point $point, Lead $lead)
    {
        $this->point = $point;
        $this->lead  = $lead;
    }

    /**
     * @return Point
     */
    public function getPoint()
    {
        return $this->point;
    }

    public function setPoint(Point $point)
    {
        $this->point = $point;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }
}
