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
    private const BEST_DEFAULT_DAYS       = ['Tuesday', 'Monday', 'Thursday'];
    private const HOUR_FORMAT             = 'G'; // 0 through 23
    private const DAY_FORMAT              = 'l';
    private const FETCH_EMAIL_READS_LIMIT = 25;
    private const FETCH_PAGE_HITS_LIMIT   = 25;
    private const MIN_INTERACTIONS        = 4;

    private ?\DateTimeZone $defaultTimezone = null;

    private int $bestHourStart = self::BEST_DEFAULT_HOUR_START;
    private int $bestHourEnd   = self::BEST_DEFAULT_HOUR_END;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private StatRepository $statRepository,
        private HitRepository $hitRepository
    ) {
    }

    /**
     * Get the optimal time for a contact.
     */
    public function getOptimalTime(Lead $contact): \DateTimeInterface
    {
        $currentDateTime = $this->getContactDateTime($contact);

        $interactions = $this->getContactInteractions($contact, $currentDateTime->getTimezone());
        if (count($interactions) > self::MIN_INTERACTIONS) {
            $hours               = array_column($interactions, 'hourOfDay');
            $medianHour          = $this->calculateMedian($hours);
            $this->bestHourStart = ($medianHour + 23) % 24; // Calculate hour before
            $this->bestHourEnd   = ($medianHour + 1) % 24; // Calculate hour after
        }

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

        return $hour >= $this->bestHourStart && $hour < $this->bestHourEnd;
    }

    private function isDayAndTimeOptimal(\DateTimeInterface $dateTime): bool
    {
        return in_array($dateTime->format(self::DAY_FORMAT), self::BEST_DEFAULT_DAYS, true) && $this->isTimeOptimal($dateTime);
    }

    private function getAdjustedDateTime(\DateTimeInterface $dateTime): \DateTimeInterface
    {
        $adjustedDateTime = clone $dateTime;
        $adjustedDateTime->setTime($this->bestHourStart, self::MINUTES_START_OF_HOUR);

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

    /**
     * @return array<string, mixed>
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
                'dayOfWeek' => $readDate->format(self::DAY_FORMAT),
                'time'      => $readDate->format('H:i:s'),
            ];
        }

        foreach ($pageHits as $interaction) {
            $hitDate        = $interaction['dateHit'];
            $interactions[] = [
                'type'      => 'page.hit',
                'date'      => $hitDate->format('Y-m-d H:i:s'),
                'dayOfWeek' => $hitDate->format('l'),
                'time'      => $hitDate->format('H:i:s'),
            ];
        }

        return $interactions;
    }

    private function getLeadStats(int $leadId): array
    {
        return $this->statRepository->getLeadStats($leadId, [
            'order'        => ['timestamp', 'DESC'],
            'limit'        => self::FETCH_EMAIL_READS_LIMIT,
            'state'        => 'read',
            'basic_select' => true,
        ]);
    }

    private function getLeadHits(int $leadId): array
    {
        return $this->hitRepository->getLeadHits($leadId, [
            'order' => ['timestamp', 'DESC'],
            'limit' => self::FETCH_PAGE_HITS_LIMIT,
        ]);
    }

    private function calculateMedian(array $arr): int
    {
        sort($arr);

        $median = 0;
        $count  = count($arr);

        if ($count > 0) {
            $middleIndex = floor(($count - 1) / 2);
            if ($count % 2) {
                // Odd number of elements, middle value is the median
                $median = $arr[$middleIndex];
            } else {
                // Even number of elements, calculate avg of the two middle values
                $median = ($arr[$middleIndex] + $arr[$middleIndex + 1]) / 2;
            }
        }

        return $median;
    }
}
