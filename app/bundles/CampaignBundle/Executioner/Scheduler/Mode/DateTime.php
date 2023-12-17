<?php

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;
use Psr\Log\LoggerInterface;

class DateTime implements ScheduleModeInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function getExecutionDateTime(Event $event, \DateTimeInterface $compareFromDateTime, \DateTimeInterface $comparedToDateTime): \DateTimeInterface
    {
        $triggerDate = $event->getTriggerDate();

        if (null === $triggerDate) {
            $this->logger->debug('CAMPAIGN: Trigger date is null');

            return $compareFromDateTime;
        }

        if ($compareFromDateTime >= $triggerDate) {
            $this->logger->debug(
                'CAMPAIGN: ('.$event->getId().') Date to execute ('.$triggerDate->format('Y-m-d H:i:s T').') compared to now ('
                .$compareFromDateTime->format('Y-m-d H:i:s T').') and is thus overdue'
            );

            return $compareFromDateTime;
        }

        return $triggerDate;
    }
}
