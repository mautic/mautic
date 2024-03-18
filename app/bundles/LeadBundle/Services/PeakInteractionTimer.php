<?php

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\HitRepository;

class PeakInteractionTimer
{
    private const BEST_DEFAULT_HOUR_START = 9; // 9 AM
    private const BEST_DEFAULT_HOUR_END   = 12; // 12 PM
    private const MINUTES_START_OF_HOUR   = 0; // Start of the hour
    private const BEST_DEFAULT_DAYS       = [2, 1, 4]; // Tuesday, Monday, Thursday
    private const HOUR_FORMAT             = 'G'; // 0 through 23
    private const DAY_FORMAT              = 'N'; // ISO 8601 numeric representation of the day of the week
    private const FETCH_EMAIL_READS_LIMIT = 25;
    private const FETCH_PAGE_HITS_LIMIT   = 25;
    private const MIN_INTERACTIONS        = 4;
    private const MAX_OPTIMAL_DAYS        = 3;

    private ?\DateTimeZone $defaultTimezone = null;

    private int $bestHourStart = self::BEST_DEFAULT_HOUR_START;
    private int $bestHourEnd   = self::BEST_DEFAULT_HOUR_END;
    /** @var int[] */
    private array $bestDays   = self::BEST_DEFAULT_DAYS;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private StatRepository $statRepository,
        private HitRepository $hitRepository
    ) {
    }

    /**
     * Get the optimal time for a contact.
     */
    public function getOptimalTime(Lead $contact): \DateTime
    {
        $currentDateTime = $this->getContactDateTime($contact);

        $interactions = $this->getContactInteractions($contact, $currentDateTime->getTimezone());
        if (count($interactions) > self::MIN_INTERACTIONS) {
            $hours                                     = array_column($interactions, 'hourOfDay');
            [$this->bestHourStart, $this->bestHourEnd] = $this->calculateOptimalTime($hours);
        }

        return $this->isTimeOptimal($currentDateTime)
            ? $currentDateTime
            : $this->getAdjustedDateTime($currentDateTime);
    }

    /**
     * Get the optimal time and day for a contact.
     */
    public function getOptimalTimeAndDay(Lead $contact): \DateTime
    {
        $currentDateTime = $this->getContactDateTime($contact);

        $interactions = $this->getContactInteractions($contact, $currentDateTime->getTimezone());
        if (count($interactions) > self::MIN_INTERACTIONS) {
            $hours                                     = array_column($interactions, 'hourOfDay');
            $days                                      = array_column($interactions, 'dayOfWeek');
            [$this->bestHourStart, $this->bestHourEnd] = $this->calculateOptimalTime($hours);
            $this->bestDays                            = $this->calculateOptimalDays($days);
        }

        return $this->isDayAndTimeOptimal($currentDateTime)
            ? $currentDateTime
            : $this->findOptimalDateTime($currentDateTime);
    }

    private function isTimeOptimal(\DateTime $dateTime): bool
    {
        $hour = (int) $dateTime->format(self::HOUR_FORMAT);

        return $hour >= $this->bestHourStart && $hour < $this->bestHourEnd;
    }

    private function isDayAndTimeOptimal(\DateTime $dateTime): bool
    {
        return in_array((int) $dateTime->format(self::DAY_FORMAT), $this->bestDays, true) && $this->isTimeOptimal($dateTime);
    }

    private function getAdjustedDateTime(\DateTime $dateTime): \DateTime
    {
        $adjustedDateTime = clone $dateTime;
        $adjustedDateTime->setTime($this->bestHourStart, self::MINUTES_START_OF_HOUR);

        return $adjustedDateTime <= $dateTime
            ? $adjustedDateTime->modify('+1 day')
            : $adjustedDateTime;
    }

    private function findOptimalDateTime(\DateTime $dateTime): \DateTime
    {
        $optimalDateTime = $this->getAdjustedDateTime($dateTime);

        while (!in_array((int) $optimalDateTime->format(self::DAY_FORMAT), $this->bestDays, true)) {
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getContactInteractions(Lead $contact, \DateTimeZone $dateTimeZone): array
    {
        $interactions = [];

        $emailReads = $this->getLeadStats($contact->getId());
        $pageHits   = $this->getLeadHits($contact->getId());

        foreach ($emailReads as $interaction) {
            /** @var \DateTime $readDate */
            $readDate = $interaction['dateRead'];
            $readDate->setTimezone($dateTimeZone);
            $interactions[] = [
                'type'      => 'email.read',
                'date'      => $readDate->format('Y-m-d H:i:s'),
                'hourOfDay' => (int) $readDate->format(self::HOUR_FORMAT),
                'dayOfWeek' => (int) $readDate->format(self::DAY_FORMAT),
                'time'      => $readDate->format('H:i:s'),
            ];
        }

        foreach ($pageHits as $interaction) {
            $hitDate        = $interaction['dateHit'];
            $interactions[] = [
                'type'      => 'page.hit',
                'date'      => $hitDate->format('Y-m-d H:i:s'),
                'hourOfDay' => (int) $hitDate->format(self::HOUR_FORMAT),
                'dayOfWeek' => (int) $hitDate->format(self::DAY_FORMAT),
                'time'      => $hitDate->format('H:i:s'),
            ];
        }

        return $interactions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLeadStats(int $leadId): array
    {
        return $this->statRepository->getLeadStats($leadId, [
            'order'        => ['timestamp', 'DESC'],
            'limit'        => self::FETCH_EMAIL_READS_LIMIT,
            'state'        => 'read',
            'basic_select' => true,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLeadHits(int $leadId): array
    {
        return $this->hitRepository->getLeadHits($leadId, [
            'order' => ['timestamp', 'DESC'],
            'limit' => self::FETCH_PAGE_HITS_LIMIT,
        ]);
    }

    /**
     * Calculates the optimal time range based on an array of elements.
     *
     * @param int[] $elements Hours (0-23)
     *
     * @return int[] Hours (0-23)
     */
    private function calculateOptimalTime(array $elements): array
    {
        sort($elements);

        $count  = count($elements);
        if ($count > 0) {
            $middleIndex = (int) floor(($count - 1) / 2);
            $result      = $elements[$middleIndex];
        } else {
            throw new \Exception('Not enough elements');
        }

        $start = ($result + 23) % 24; // hour before
        $end   = ($result + 1) % 24;   // hour after

        // Return the start and end hours as an array
        return [$start, $end];
    }

    /**
     * Calculates the optimal days based on the frequency of elements.
     *
     * @param int[] $elements Days of the week (ISO 8601)
     *
     * @return int[] Days of the week (ISO 8601)
     */
    private function calculateOptimalDays(array $elements): array
    {
        if (0 === count($elements)) {
            throw new \Exception('Not enough elements');
        }

        // Count the frequency of each element.
        $frequency = array_count_values($elements);

        // Sort frequencies in descending order.
        arsort($frequency);

        // Get the elements sorted by frequency.
        $optimalDays = array_keys($frequency);

        // Return the top elements up to the max optimal days limit.
        return array_slice($optimalDays, 0, min(self::MAX_OPTIMAL_DAYS, count($optimalDays)));
    }
}
