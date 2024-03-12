<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;

class PeakInteractionTimer
{
    public const DEFAULT_HOUR_START = 9; // 9 AM
    public const DEFAULT_HOUR_END   = 12; // 12 PM
    public const DEFAULT_DAYS       = ['Tuesday', 'Monday', 'Thursday'];

    private ?\DateTimeZone $defaultTimezone = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function getOptimalTime(Lead $contact): \DateTimeInterface
    {
        if ($contactTimezoneString = $contact->getTimezone()) {
            $contactTimezone = new \DateTimeZone($contactTimezoneString);
        } else {
            $contactTimezone = $this->getDefaultTimezone();
        }

        $currentDateTime = $this->getCurrentDateTime($contactTimezone);

        $firstOptimalToday = clone $currentDateTime;
        $firstOptimalToday->setTime(self::DEFAULT_HOUR_START, 0);
        $lastOptimalToday = clone $currentDateTime;
        $lastOptimalToday->setTime(self::DEFAULT_HOUR_END, 0);

        // Check if it's optimal now
        if ($currentDateTime <= $lastOptimalToday && $currentDateTime >= $firstOptimalToday) {
            return $currentDateTime;
        }

        // Use the best default interaction hour
        $optimalDateTime = clone $currentDateTime;
        $optimalDateTime->setTime(self::DEFAULT_HOUR_START, 0);

        // If the current time is past the optimal time range, move to the next day
        if ($optimalDateTime < $currentDateTime) {
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
