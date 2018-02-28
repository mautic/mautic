<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;
use Psr\Log\LoggerInterface;

class DateTime implements ScheduleModeInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * EventScheduler constructor.
     *
     * @param LoggerInterface $logger
     * @param \DateTime       $now
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Event     $event
     * @param \DateTime $now
     * @param \DateTime $comparedToDateTime
     *
     * @return \DateTime|mixed
     */
    public function getExecutionDateTime(Event $event, \DateTime $now, \DateTime $comparedToDateTime)
    {
        $triggerDate = $event->getTriggerDate();

        if (null === $triggerDate) {
            $this->logger->debug('CAMPAIGN: Trigger date is null');

            return $now;
        }

        if ($now >= $triggerDate) {
            $this->logger->debug(
                'CAMPAIGN: ('.$event->getId().') Date to execute ('.$triggerDate->format('Y-m-d H:i:s T').') compared to now ('
                .$now->format('Y-m-d H:i:s T').') and is thus overdue'
            );

            return $now;
        }

        return $triggerDate;
    }
}
