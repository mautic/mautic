<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;

class Behavioral implements ScheduleModeInterface
{
    public function getExecutionDateTime(Event $event, \DateTimeInterface $now, \DateTimeInterface $comparedToDateTime): \DateTimeInterface
    {
        return $now;
    }

    public function getExecutionDateTimeForContact(Event $event, Lead $contact, \DateTimeInterface $executionDate): \DateTimeInterface
    {
        // todo get the behavioral datetime
        return $executionDate;
    }
}
