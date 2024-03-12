<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;

class PeakInteractionTimer
{
    private const BEST_DEFAULT_HOUR_START = 9; // 9 AM
    private const BEST_DEFAULT_HOUR_END   = 12; // 12 PM
    private const MINUTES_START_OF_HOUR   = 0;    // Start of the hour
    private const BEST_DEFAULT_DAYS       = ['Tuesday', 'Monday', 'Thursday'];

    private ?\DateTimeZone $defaultTimezone = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function getOptimalTime(Lead $contact): \DateTimeInterface
    {
        $contactTimezoneString = $contact->getTimezone();
        $contactTimezone       = $contactTimezoneString ? new \DateTimeZone($contactTimezoneString) : $this->getDefaultTimezone();

        $currentDateTime = $this->getCurrentDateTime($contactTimezone);

        $firstOptimalToday = clone $currentDateTime;
        $firstOptimalToday->setTime(self::BEST_DEFAULT_HOUR_START, self::MINUTES_START_OF_HOUR);
        $lastOptimalToday = clone $currentDateTime;
        $lastOptimalToday->setTime(self::BEST_DEFAULT_HOUR_END, self::MINUTES_START_OF_HOUR);

        // Return current time if it's within the optimal range
        if ($currentDateTime >= $firstOptimalToday && $currentDateTime <= $lastOptimalToday) {
            return $currentDateTime;
        }

        // Set time to the start of the optimal range and adjust to the next day if needed
        $optimalDateTime = clone $currentDateTime;
        $optimalDateTime->setTime(self::BEST_DEFAULT_HOUR_START, self::MINUTES_START_OF_HOUR);
        if ($optimalDateTime <= $currentDateTime) {
            $optimalDateTime->modify('+1 day');
        }

        return $optimalDateTime;
    }

    protected function getCurrentDateTime(\DateTimeZone $timezone): \DateTime
    {
        return new \DateTime('now', $timezone);
    }

    private function getDefaultTimezone(): \DateTimeZone
    {
        if ($this->defaultTimezone) {
            return $this->defaultTimezone;
        }

        $this->defaultTimezone = new \DateTimeZone(
            $this->coreParametersHelper->get('default_timezone', 'UTC')
        );

        return $this->defaultTimezone;
    }
}
