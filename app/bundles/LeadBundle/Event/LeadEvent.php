<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

final class LeadEvent extends CommonEvent
{
    public function __construct(
        Lead $lead,
        bool $isNew = false,
    ) {
        $this->entity = $lead;
        $this->isNew  = $isNew;
    }

    public function getLead(): Lead
    {
        return $this->entity;
    }

    public function setLead(Lead $lead): void
    {
        $this->entity = $lead;
    }
}
