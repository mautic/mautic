<?php

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class ApiEvent extends CommonEvent
{
    private $lead;
    private $idRule;

    public function __construct(Lead $lead, $IdRule, $isNew = false)
    {
        $this->lead   = $lead;
        $this->idRule = $IdRule;
        $this->isNew  = $isNew;
    }

    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function getIdRule()
    {
        return $this->idRule;
    }

    public function setIdRule($id)
    {
        $this->idRule = $id;
    }
}
