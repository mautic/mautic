<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;

interface ScheduleModeInterface
{
    /**
     * @return \DateTime
     */
    public function getExecutionDateTime(Event $event, \DateTime $now, \DateTime $comparedToDateTime);
}
