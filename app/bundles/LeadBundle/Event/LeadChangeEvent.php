<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class LeadChangeEvent extends Event
{
    public function __construct(
        private Lead $oldLead,
        private $oldTrackingId,
        private Lead $newLead,
        private $newTrackingId,
    ) {
    }

    public function getOldLead(): Lead
    {
        return $this->oldLead;
    }

    /**
     * @return mixed
     */
    public function getOldTrackingId()
    {
        return $this->oldTrackingId;
    }

    public function getNewLead(): Lead
    {
        return $this->newLead;
    }

    /**
     * @return mixed
     */
    public function getNewTrackingId()
    {
        return $this->newTrackingId;
    }
}
