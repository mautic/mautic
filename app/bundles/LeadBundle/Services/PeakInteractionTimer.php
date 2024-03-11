<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;

class PeakInteractionTimer
{
    private $bestDefaultHourStart = 9; // 9 AM
    private $bestDefaultHourEnd   = 12;  // 12 PM

    private $defaultSendingDays = ['Tuesday', 'Monday', 'Thursday'];

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

        $currentDateTime = new \DateTime('now', $contactTimezone);

        // Use the best default interaction hour
        $lastOptimalToday = clone $currentDateTime;
        $lastOptimalToday->setTime($this->bestDefaultHourEnd, 0);

        // Check if it's optimal now
        if ($currentDateTime < $lastOptimalToday && $currentDateTime->format('H') >= $this->bestDefaultHourStart) {
            return $currentDateTime;
        }

        // Use the best default interaction hour
        $optimalDateTime = clone $currentDateTime;
        $optimalDateTime->setTime(rand($this->bestDefaultHourStart, $this->bestDefaultHourEnd - 1), rand(0, 59));

        // If the current time is past the optimal time range, move to the next day
        if ($optimalDateTime < new \DateTime('now', $contactTimezone)) {
            $optimalDateTime->modify('+1 day');
            $optimalDateTime->setTime($this->bestDefaultHourStart, 0);
        }

        return $optimalDateTime;
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
