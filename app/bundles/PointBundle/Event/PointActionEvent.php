<?php

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;

class PointActionEvent extends CommonEvent
{
    public function __construct(
        protected Point $point,
        protected Lead $lead,
    ) {
    }

    public function getPoint(): Point
    {
        return $this->point;
    }

    public function setPoint(Point $point): void
    {
        $this->point = $point;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
    {
        $this->lead = $lead;
    }
}
