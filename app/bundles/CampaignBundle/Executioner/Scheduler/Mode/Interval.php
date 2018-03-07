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
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Psr\Log\LoggerInterface;

class Interval implements ScheduleModeInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Interval constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Event     $event
     * @param \DateTime $compareFromDateTime
     * @param \DateTime $comparedToDateTime
     *
     * @return \DateTime
     *
     * @throws NotSchedulableException
     */
    public function getExecutionDateTime(Event $event, \DateTime $compareFromDateTime, \DateTime $comparedToDateTime)
    {
        $interval = $event->getTriggerInterval();
        $unit     = $event->getTriggerIntervalUnit();

        // Prevent comparisons from modifying original object
        $comparedToDateTime = clone $comparedToDateTime;

        try {
            $this->logger->debug(
                'CAMPAIGN: ('.$event->getId().') Adding interval of '.$interval.$unit.' to '.$comparedToDateTime->format('Y-m-d H:i:s T')
            );
            $comparedToDateTime->add((new DateTimeHelper())->buildInterval($interval, $unit));
        } catch (\Exception $exception) {
            $this->logger->error('CAMPAIGN: Determining interval scheduled failed with "'.$exception->getMessage().'"');

            throw new NotSchedulableException();
        }

        if ($comparedToDateTime > $compareFromDateTime) {
            $this->logger->debug(
                "CAMPAIGN: Interval of $interval $unit to execute (".$comparedToDateTime->format('Y-m-d H:i:s T').') is later than now ('
                .$compareFromDateTime->format('Y-m-d H:i:s T')
            );

            //the event is to be scheduled based on the time interval
            return $comparedToDateTime;
        }

        return $compareFromDateTime;
    }
}
