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

    /**
     * @return Lead
     */
    public function getOldLead()
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

    /**
     * @return Lead
     */
    public function getNewLead()
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
