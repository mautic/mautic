<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Services\PeakInteractionTimer;

class Behavioral implements ScheduleModeInterface
{
    public function __construct(
        private PeakInteractionTimer $peakInteractionTimer
    ) {
    }

    public function getExecutionDateTime(Event $event, \DateTimeInterface $now, \DateTimeInterface $comparedToDateTime): \DateTimeInterface
    {
        return $now;
    }

    public function getExecutionDateTimeForContact(Event $event, Lead $contact, \DateTimeInterface $executionDate): \DateTimeInterface
    {
        return $this->peakInteractionTimer->getOptimalTime($contact);
    }
}
