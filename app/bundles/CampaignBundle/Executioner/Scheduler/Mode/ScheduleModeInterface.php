<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;

interface ScheduleModeInterface
{
    public function getExecutionDateTime(Event $event, \DateTimeInterface $now, \DateTimeInterface $comparedToDateTime): \DateTimeInterface;
}
