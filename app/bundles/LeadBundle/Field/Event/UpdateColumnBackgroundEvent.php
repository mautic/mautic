<?php

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateColumnBackgroundEvent extends Event
{
    public function __construct(private LeadField $leadField)
    {
    }

    /**
     * @return LeadField
     */
    public function getLeadField()
    {
        return $this->leadField;
    }
}
