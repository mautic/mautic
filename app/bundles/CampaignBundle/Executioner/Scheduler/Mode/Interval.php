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

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DAO\GroupExecutionDateDAO;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;

class Interval implements ScheduleModeInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var \DateTimeZone
     */
    private $defaultTimezone;

    /**
     * Interval constructor.
     *
     * @param LoggerInterface      $logger
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(LoggerInterface $logger, CoreParametersHelper $coreParametersHelper)
    {
        $this->logger               = $logger;
        $this->coreParametersHelper = $coreParametersHelper;
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
                'CAMPAIGN: ('.$event->getId().') '.$comparedToDateTime->format('Y-m-d H:i:s T').' is later than '
                .$compareFromDateTime->format('Y-m-d H:i:s T').' and thus returning '.$comparedToDateTime->format('Y-m-d H:i:s T')
            );

            //the event is to be scheduled based on the time interval
            return $comparedToDateTime;
        }

        $this->logger->debug(
            'CAMPAIGN: ('.$event->getId().') '.$comparedToDateTime->format('Y-m-d H:i:s T').' is earlier than '
            .$compareFromDateTime->format('Y-m-d H:i:s T').' and thus returning '.$compareFromDateTime->format('Y-m-d H:i:s T')
        );

        return $compareFromDateTime;
    }

    /**
     * @param LeadEventLog $log
     * @param \DateTime    $currentDateTime
     *
     * @return \DateTime
     *
     * @throws NotSchedulableException
     */
    public function validateExecutionDateTime(LeadEventLog $log, \DateTime $currentDateTime)
    {
        $event         = $log->getEvent();
        $dateTriggered = clone $log->getDateTriggered();
        $hour          = $event->getTriggerHour();

        if (empty($hour)) {
            return $this->getExecutionDateTime($event, $currentDateTime, $dateTriggered);
        }

        $diff = $dateTriggered->diff($currentDateTime);

        return $this->convertToHourInContactTimezone($log->getLead(), $hour, $diff, $event->getId(), $currentDateTime);
    }

    /**
     * @param Event           $event
     * @param ArrayCollection $contacts
     * @param \DateTime       $executionDate
     *
     * @return GroupExecutionDateDAO[]
     */
    public function groupContactsByDate(Event $event, ArrayCollection $contacts, \DateTime $executionDate)
    {
        $groupedExecutionDates = [];
        $hour                  = $event->getTriggerHour();

        $diff = (new \Datetime())->diff($executionDate);

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            $groupExecutionDate = $this->convertToHourInContactTimezone($contact, $hour, $diff, $event->getId());

            if (!isset($groupedExecutionDates[$groupExecutionDate->getTimestamp()])) {
                $groupedExecutionDates[$groupExecutionDate->getTimestamp()] = new GroupExecutionDateDAO($groupExecutionDate);
            }

            $groupedExecutionDates[$groupExecutionDate->getTimestamp()]->addContact($contact);
        }

        return $groupedExecutionDates;
    }

    /**
     * @param Lead           $contact
     * @param \DateTime      $hour
     * @param \DateInterval  $diff
     * @param                $eventId
     * @param \DateTime|null $currentDateTime
     *
     * @return \DateTime
     */
    private function convertToHourInContactTimezone(Lead $contact, \DateTime $hour, \DateInterval $diff, $eventId, \DateTime $currentDateTime = null)
    {
        $groupHour = clone $hour;
        $now       = $currentDateTime ? $currentDateTime : new \DateTime('now', $this->getDefaultTimezone());

        // Set execution to UTC
        if ($timezone = $contact->getTimezone()) {
            try {
                // Set the group's timezone to the contact's
                $contactTimezone = new \DateTimeZone($timezone);

                $this->logger->debug(
                    'CAMPAIGN: ('.$eventId.') Setting '.$timezone.' for contact '.$contact->getId()
                );

                // Get now in the contacts timezone then add the number of days from now and the original execution date
                $now->setTimezone($contactTimezone);

                $groupExecutionDate = clone $now;
                $groupExecutionDate->modify(sprintf('+%d days', $diff->days));

                $groupExecutionDate->setTime($groupHour->format('H'), $groupHour->format('i'));

                return $groupExecutionDate;
            } catch (\Exception $exception) {
                // Timezone is not recognized so use the default
                $this->logger->debug(
                    'CAMPAIGN: ('.$eventId.') '.$timezone.' for contact '.$contact->getId().' is not recognized'
                );
            }
        }

        $groupExecutionDate = clone $now;
        $groupExecutionDate->modify(sprintf('+%d days', $diff->days));

        $groupExecutionDate->setTime($groupHour->format('H'), $groupHour->format('i'));

        return $groupExecutionDate;
    }

    /**
     * @return \DateTimeZone
     */
    private function getDefaultTimezone()
    {
        if ($this->defaultTimezone) {
            return $this->defaultTimezone;
        }

        $this->defaultTimezone = new \DateTimeZone(
            $this->coreParametersHelper->getParameter('default_timezone', 'UTC')
        );

        return $this->defaultTimezone;
    }
}
