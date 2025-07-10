<?php

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
    public const LOG_DATE_FORMAT = 'Y-m-d H:i:s T';

    private ?\DateTimeZone $defaultTimezone = null;

    public function __construct(
        private LoggerInterface $logger,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * @throws NotSchedulableException
     */
    public function getExecutionDateTime(Event $event, \DateTimeInterface $compareFromDateTime, \DateTimeInterface $comparedToDateTime): \DateTimeInterface
    {
        $interval = $event->getTriggerInterval();
        $unit     = $event->getTriggerIntervalUnit();

        try {
            $this->logger->debug(
                'CAMPAIGN: ('.$event->getId().') Adding interval of '.$interval.$unit.' to '.$comparedToDateTime->format(self::LOG_DATE_FORMAT)
            );
            /** @var \DateTime $comparedToDateTime */
            $comparedToDateTime->add((new DateTimeHelper())->buildInterval($interval, $unit));
        } catch (\Exception $exception) {
            $this->logger->error('CAMPAIGN: Determining interval scheduled failed with "'.$exception->getMessage().'"');

            throw new NotSchedulableException($exception->getMessage());
        }

        if ($comparedToDateTime > $compareFromDateTime) {
            $this->logger->debug(
                'CAMPAIGN: ('.$event->getId().') '.$comparedToDateTime->format(self::LOG_DATE_FORMAT).' is later than '
                .$compareFromDateTime->format(self::LOG_DATE_FORMAT).' and thus returning '.$comparedToDateTime->format(self::LOG_DATE_FORMAT)
            );

            // the event is to be scheduled based on the time interval
            return $comparedToDateTime;
        }

        $this->logger->debug(
            'CAMPAIGN: ('.$event->getId().') '.$comparedToDateTime->format(self::LOG_DATE_FORMAT).' is earlier than '
            .$compareFromDateTime->format(self::LOG_DATE_FORMAT).' and thus returning '.$compareFromDateTime->format(self::LOG_DATE_FORMAT)
        );

        return $compareFromDateTime;
    }

    /**
     * @return \DateTimeInterface
     *
     * @throws NotSchedulableException
     */
    public function validateExecutionDateTime(LeadEventLog $log, \DateTimeInterface $compareFromDateTime)
    {
        $event         = $log->getEvent();
        $dateTriggered = clone $log->getDateTriggered();

        if (!$this->isContactSpecificExecutionDateRequired($event)) {
            return $this->getExecutionDateTime($event, $compareFromDateTime, $dateTriggered);
        }

        $interval      = $event->getTriggerInterval();
        $unit          = $event->getTriggerIntervalUnit();

        if ($interval && $unit) {
            /** @var \DateTime $dateTriggered */
            $dateTriggered->add((new DateTimeHelper())->buildInterval($interval, $unit));
        }

        if ($dateTriggered < $compareFromDateTime) {
            $this->logger->debug(
                sprintf('CAMPAIGN: (%s) %s is earlier than %s and thus setting %s', $event->getId(), $dateTriggered->format(self::LOG_DATE_FORMAT), $compareFromDateTime->format(self::LOG_DATE_FORMAT), $compareFromDateTime->format(self::LOG_DATE_FORMAT))
            );
            $dateTriggered = clone $compareFromDateTime;
        }

        $hour      = $event->getTriggerHour();
        $startTime = $event->getTriggerRestrictedStartHour();
        $endTime   = $event->getTriggerRestrictedStopHour();
        $dow       = $event->getTriggerRestrictedDaysOfWeek();

        return $this->getGroupExecutionDateTime($event->getId(), $log->getLead(), $dateTriggered, $hour, $startTime, $endTime, $dow);
    }

    /**
     * @return GroupExecutionDateDAO[]
     */
    public function groupContactsByDate(Event $event, ArrayCollection $contacts, \DateTimeInterface $executionDate, \DateTimeInterface $compareFromDateTime = null): array
    {
        $groupedExecutionDates = [];
        $hour                  = $event->getTriggerHour();
        $startTime             = $event->getTriggerRestrictedStartHour();
        $endTime               = $event->getTriggerRestrictedStopHour();
        $daysOfWeek            = $event->getTriggerRestrictedDaysOfWeek();

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            $groupExecutionDate = $this->getGroupExecutionDateTime(
                $event->getId(),
                $contact,
                $executionDate,
                $hour,
                $startTime,
                $endTime,
                $daysOfWeek
            );
            $key = $groupExecutionDate->format(DateTimeHelper::FORMAT_DB);
            if (!isset($groupedExecutionDates[$key])) {
                $groupedExecutionDates[$key] = new GroupExecutionDateDAO($groupExecutionDate);
            }

            $groupedExecutionDates[$key]->addContact($contact);
        }

        return $groupedExecutionDates;
    }

    /**
     * Checks if an event has a relative time configured.
     */
    public function isContactSpecificExecutionDateRequired(Event $event): bool
    {
        if ($this->isTriggerModeOptimized($event)) {
            return true;
        }

        if (!$this->isTriggerModeInterval($event) || $this->isRestrictedToDailyScheduling($event) || $this->hasTimeRelatedRestrictions($event) || $this->isNegativePath($event)) {
            return false;
        }

        return true;
    }

    private function isTriggerModeInterval(Event $event): bool
    {
        return Event::TRIGGER_MODE_INTERVAL === $event->getTriggerMode();
    }

    private function isTriggerModeOptimized(Event $event): bool
    {
        return Event::TRIGGER_MODE_OPTIMIZED === $event->getTriggerMode();
    }

    private function isRestrictedToDailyScheduling(Event $event): bool
    {
        return !in_array($event->getTriggerIntervalUnit(), ['i', 'h', 'd', 'm', 'y'])
            && empty($event->getTriggerRestrictedDaysOfWeek());
    }

    private function hasTimeRelatedRestrictions(Event $event): bool
    {
        return null === $event->getTriggerHour()
            && (null === $event->getTriggerRestrictedStartHour() || null === $event->getTriggerRestrictedStopHour())
            && empty($event->getTriggerRestrictedDaysOfWeek());
    }

    private function isNegativePath(Event $event): bool
    {
        if ($event->getParent()) {
            return Event::TYPE_DECISION === $event->getParent()->getEventType() && Event::TYPE_ACTION === $event->getEventType() && Event::PATH_INACTION === $event->getDecisionPath();
        }

        return false;
    }

    /**
     * @return \DateTimeInterface
     */
    private function getGroupExecutionDateTime(
        $eventId,
        Lead $contact,
        \DateTimeInterface $compareFromDateTime,
        \DateTimeInterface $hour = null,
        \DateTimeInterface $startTime = null,
        \DateTimeInterface $endTime = null,
        array $daysOfWeek = [],
    ) {
        $this->logger->debug(
            sprintf('CAMPAIGN: Comparing calculated executed time for event ID %s and contact ID %s with %s', $eventId, $contact->getId(), $compareFromDateTime->format('Y-m-d H:i:s e'))
        );

        if ($hour) {
            $this->logger->debug(
                sprintf('CAMPAIGN: Scheduling event ID %s for contact ID %s based on hour of %s', $eventId, $contact->getId(), $hour->format('H:i e'))
            );
            $groupDateTime = $this->getExecutionDateTimeFromHour($contact, $hour, $eventId, $compareFromDateTime);
        } elseif ($startTime && $endTime) {
            $this->logger->debug(
                sprintf(
                    'CAMPAIGN: Scheduling event ID %s for contact ID %s based on hour range of %s to %s',
                    $eventId,
                    $contact->getId(),
                    $startTime->format('H:i e'),
                    $endTime->format('H:i e')
                )
            );

            $groupDateTime = $this->getExecutionDateTimeBetweenHours($contact, $startTime, $endTime, $eventId, $compareFromDateTime);
        } else {
            $this->logger->debug(
                sprintf('CAMPAIGN: Scheduling event ID %s for contact ID %s without hour restrictions.', $eventId, $contact->getId())
            );

            $groupDateTime = clone $compareFromDateTime;
        }

        if ([] !== $daysOfWeek) {
            $this->logger->debug(
                sprintf(
                    'CAMPAIGN: Scheduling event ID %s for contact ID %s based on DOW restrictions of %s',
                    $eventId,
                    $contact->getId(),
                    implode(',', $daysOfWeek)
                )
            );

            if (in_array(7, $daysOfWeek, true) || in_array('7', $daysOfWeek, true)) {
                throw new \LogicException('The Mautic accepts only 0-6 as day of week (0 is Sunday).');
            }

            // Schedule for the next day of the week if applicable
            while (!in_array((int) $groupDateTime->format('w'), $daysOfWeek)) {
                /** @var \DateTime $groupDateTime */
                $groupDateTime->modify('+1 day');
            }
        }

        return $groupDateTime;
    }

    /**
     * @return \DateTimeInterface
     */
    private function getExecutionDateTimeFromHour(Lead $contact, \DateTimeInterface $hour, $eventId, \DateTimeInterface $compareFromDateTime)
    {
        /** @var \DateTime $groupHour */
        $groupHour = clone $hour;

        /** @var \DateTime $groupExecutionDate */
        $groupExecutionDate = $this->getGroupExecutionDateWithTimeZone($contact, $eventId, $compareFromDateTime);
        $groupExecutionDate->setTime((int) $groupExecutionDate->format('H'), (int) $groupExecutionDate->format('i'));

        $testGroupHour = clone $groupExecutionDate;
        $testGroupHour->setTime($groupHour->format('H'), $groupHour->format('i'));

        if ($groupExecutionDate <= $testGroupHour) {
            // Schedule for the configured hour today if it's not passed yet.
            return $testGroupHour;
        }

        // Execute rigt away if the hour has passed.
        return $groupExecutionDate;
    }

    /**
     * @return \DateTimeInterface
     */
    private function getExecutionDateTimeBetweenHours(
        Lead $contact,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        $eventId,
        \DateTimeInterface $compareFromDateTime,
    ) {
        /* @var \DateTime $startTime */
        $startTime = clone $startTime;
        /* @var \DateTime $endTime */
        $endTime   = clone $endTime;

        if ($endTime < $startTime) {
            // End time is after start time so switch them
            $tempStartTime = clone $startTime;
            $startTime     = clone $endTime;
            $endTime       = clone $tempStartTime;
            unset($tempStartTime);
        }

        /** @var \DateTime $groupExecutionDate */
        $groupExecutionDate = $this->getGroupExecutionDateWithTimeZone($contact, $eventId, $compareFromDateTime);

        // Is the time between the start and end hours?
        $testStartDateTime = clone $groupExecutionDate;
        $testStartDateTime->setTime($startTime->format('H'), $startTime->format('i'));

        $testStopDateTime = clone $groupExecutionDate;
        $testStopDateTime->setTime($endTime->format('H'), $endTime->format('i'));

        if ($groupExecutionDate < $testStartDateTime) {
            // Too early so set it to the start date
            return $testStartDateTime;
        }

        if ($groupExecutionDate >= $testStopDateTime) {
            // Too late so try again tomorrow
            $groupExecutionDate->modify('+1 day')->setTime((int) $startTime->format('H'), (int) $startTime->format('i'));
        }

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
            $this->coreParametersHelper->get('default_timezone', 'UTC')
        );

        return $this->defaultTimezone;
    }

    private function getGroupExecutionDateWithTimeZone(Lead $contact, int $eventId, \DateTimeInterface $compareFromDateTime): \DateTimeInterface
    {
        /** @var \DateTime $groupExecutionDate */
        $groupExecutionDate = clone $compareFromDateTime;
        $contactTimezone    = $this->getDefaultTimezone();
        // Set execution to UTC
        if ($timezone = $contact->getTimezone()) {
            try {
                // Set the group's timezone to the contact's
                $contactTimezone = new \DateTimeZone($timezone);

                $this->logger->debug(
                    'CAMPAIGN: ('.$eventId.') Setting '.$timezone.' for contact '.$contact->getId()
                );
            } catch (\Exception) {
                // Timezone is not recognized so use the default
                $this->logger->debug(
                    'CAMPAIGN: ('.$eventId.') '.$timezone.' for contact '.$contact->getId().' is not recognized'
                );
            }
        }

        $groupExecutionDate->setTimezone($contactTimezone);

        return $groupExecutionDate;
    }
}
