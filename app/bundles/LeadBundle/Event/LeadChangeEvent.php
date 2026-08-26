<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class LeadChangeEvent extends Event
{
    public function __construct(
        private readonly Lead $oldLead,
        private readonly string $oldTrackingId,
        private readonly Lead $newLead,
        private $newTrackingId,
    ) {
    }

    public function getOldLead(): Lead
    {
        return $this->oldLead;
    }

    public function getOldTrackingId(): string
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
