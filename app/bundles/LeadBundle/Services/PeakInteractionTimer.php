<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;

class PeakInteractionTimer
{
    private const BEST_DEFAULT_HOUR_START = 9; // 9 AM
    private const BEST_DEFAULT_HOUR_END   = 12; // 12 PM
    private const MINUTES_START_OF_HOUR   = 0; // Start of the hour
    private const BEST_DEFAULT_DAYS       = ['Tuesday', 'Monday', 'Thursday'];
    private const HOUR_FORMAT             = 'G';
    private const DAY_FORMAT              = 'l';

    private ?\DateTimeZone $defaultTimezone = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    /**
     * Get the optimal time for a contact.
     */
    public function getOptimalTime(Lead $contact): \DateTimeInterface
    {
        $currentDateTime = $this->getContactDateTime($contact);

        return $this->isTimeOptimal($currentDateTime)
            ? $currentDateTime
            : $this->getAdjustedDateTime($currentDateTime);
    }

    /**
     * Get the optimal time and day for a contact.
     */
    public function getOptimalTimeAndDay(Lead $contact): \DateTimeInterface
    {
        $currentDateTime = $this->getContactDateTime($contact);

        return $this->isDayAndTimeOptimal($currentDateTime)
            ? $currentDateTime
            : $this->findOptimalDateTime($currentDateTime);
    }

    private function isTimeOptimal(\DateTimeInterface $dateTime): bool
    {
        $hour = (int) $dateTime->format(self::HOUR_FORMAT);

        return $hour >= self::BEST_DEFAULT_HOUR_START && $hour < self::BEST_DEFAULT_HOUR_END;
    }

    private function isDayAndTimeOptimal(\DateTimeInterface $dateTime): bool
    {
        return in_array($dateTime->format(self::DAY_FORMAT), self::BEST_DEFAULT_DAYS, true) && $this->isTimeOptimal($dateTime);
    }

    private function getAdjustedDateTime(\DateTimeInterface $dateTime): \DateTimeInterface
    {
        $adjustedDateTime = clone $dateTime;
        $adjustedDateTime->setTime(self::BEST_DEFAULT_HOUR_START, self::MINUTES_START_OF_HOUR);

        return $adjustedDateTime <= $dateTime
            ? $adjustedDateTime->modify('+1 day')
            : $adjustedDateTime;
    }

    private function findOptimalDateTime(\DateTimeInterface $dateTime): \DateTimeInterface
    {
        $optimalDateTime = $this->getAdjustedDateTime($dateTime);

        while (!in_array($optimalDateTime->format(self::DAY_FORMAT), self::BEST_DEFAULT_DAYS, true)) {
            $optimalDateTime->modify('+1 day');
        }

        return $optimalDateTime;
    }

    private function getContactDateTime(Lead $contact): \DateTime
    {
        $timezone = $contact->getTimezone() ? new \DateTimeZone($contact->getTimezone()) : $this->getDefaultTimezone();

        return $this->getCurrentDateTime($timezone);
    }

    protected function getCurrentDateTime(\DateTimeZone $timezone): \DateTime
    {
        return new \DateTime('now', $timezone);
    }

    private function getDefaultTimezone(): \DateTimeZone
    {
        return $this->defaultTimezone ??= new \DateTimeZone(
            $this->coreParametersHelper->get('default_timezone', 'UTC')
        );
    }
}
