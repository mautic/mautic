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
     * @param Event          $event
     * @param \DateTime|null $comparedToDateTime
     *
     * @return \Datetime
     *
     * @throws NotSchedulableException
     */
    public function getExecutionDateTime(Event $event, \DateTime $now, \DateTime $comparedToDateTime)
    {
        //                 $triggerOn = $negate ? clone $parentTriggeredDate : new \DateTime();
        $interval = $event->getTriggerInterval();
        $unit     = $event->getTriggerIntervalUnit();

        $this->logger->debug('CAMPAIGN: Adding interval of '.$interval.$unit.' to '.$comparedToDateTime->format('Y-m-d H:i:s T'));

        try {
            $comparedToDateTime->add((new DateTimeHelper())->buildInterval($interval, $unit));
        } catch (\Exception $exception) {
            $this->logger->error('CAMPAIGN: Determining interval scheduled failed with "'.$exception->getMessage().'"');

            throw new NotSchedulableException();
        }

        if ($comparedToDateTime > $now) {
            $this->logger->debug("CAMPAIGN: Interval of $interval $unit to execute (".$comparedToDateTime->format('Y-m-d H:i:s T').') is later than now ('.$now->format('Y-m-d H:i:s T'));

            //the event is to be scheduled based on the time interval
            return $comparedToDateTime;
        }

        return $now;
    }
}
