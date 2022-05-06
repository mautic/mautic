<?php

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;

class PointActionEvent extends CommonEvent
{
    /**
     * @var Point
     */
    protected $point;

    /**
     * @var Lead
     */
    protected $lead;

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
