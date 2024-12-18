<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\HitRepository;

class PeakInteractionTimer
{
    public const DEFAULT_BEST_HOUR_START         = 9; // 9 AM
    public const DEFAULT_BEST_HOUR_END           = 12; // 12 PM
    public const DEFAULT_BEST_DAYS               = [2, 1, 4]; // Tuesday, Monday, Thursday
    public const DEFAULT_FETCH_INTERACTIONS_FROM = '-60 days';
    public const DEFAULT_FETCH_LIMIT             = 50;
    public const DEFAULT_CACHE_TIMEOUT           = 43800; // in minutes ~ 1 month
    public const MIN_INTERACTIONS                = 5;
    public const DEFAULT_MAX_OPTIMAL_DAYS        = 3;

    private const MINUTES_START_OF_HOUR   = 0; // Start of the hour
    private const HOUR_FORMAT             = 'G'; // 0 through 23
    private const DAY_FORMAT              = 'N'; // ISO 8601 numeric representation of the day of the week

    private ?\DateTimeZone $defaultTimezone = null;

    private int $cacheTimeout;
    private int $bestHourStart;
    private int $bestDefaultHourStart;
    private int $bestHourEnd;
    private int $bestDefaultHourEnd;
    /** @var int[] */
    private array $bestDays;
    /** @var int[] */
    private array $bestDefaultDays;
    private string $fetchInteractionsFrom;
    private int $fetchLimit;
    private int $maxOptimalDays;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private StatRepository $statRepository,
        private HitRepository $hitRepository,
        private SubmissionRepository $submissionRepository,
        private CacheProviderInterface $cacheProvider,
    ) {
        $this->cacheTimeout          = $this->coreParametersHelper->get('peak_interaction_timer_cache_timeout');
        $this->bestDefaultHourStart  = $this->coreParametersHelper->get('peak_interaction_timer_best_default_hour_start');
        $this->bestDefaultHourEnd    = $this->coreParametersHelper->get('peak_interaction_timer_best_default_hour_end');
        $this->bestDefaultDays       = $this->coreParametersHelper->get('peak_interaction_timer_best_default_days');
        $this->fetchInteractionsFrom = $this->coreParametersHelper->get('peak_interaction_timer_fetch_interactions_from');
        $this->fetchLimit            = $this->coreParametersHelper->get('peak_interaction_timer_fetch_limit');
        $this->maxOptimalDays        = count($this->bestDefaultDays);
    }

    /**
     * Get the optimal time for a contact.
     */
    public function getOptimalTime(Lead $contact): \DateTime
    {
        $this->resetBias();
        $currentDateTime = $this->getContactDateTime($contact);

        $interactions = $this->getContactInteractions($contact, $currentDateTime->getTimezone());
        if (count($interactions) >= self::MIN_INTERACTIONS) {
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
        $this->resetBias();
        $currentDateTime = $this->getContactDateTime($contact);

        $interactions = $this->getContactInteractions($contact, $currentDateTime->getTimezone());
        if (count($interactions) >= self::MIN_INTERACTIONS) {
            $hours                                     = array_column($interactions, 'hourOfDay');
            $days                                      = array_column($interactions, 'dayOfWeek');
            [$this->bestHourStart, $this->bestHourEnd] = $this->calculateOptimalTime($hours);
            $this->bestDays                            = $this->calculateOptimalDays($days);
        }

        return $this->isDayAndTimeOptimal($currentDateTime)
            ? $currentDateTime
            : $this->findOptimalDateTime($currentDateTime);
    }

    private function resetBias(): void
    {
        $this->bestHourStart  = (int) $this->bestDefaultHourStart;
        $this->bestHourEnd    = (int) $this->bestDefaultHourEnd;
        $bestDays             = array_map('intval', $this->bestDefaultDays);
        $this->bestDays       = !empty($bestDays) ? $bestDays : self::DEFAULT_BEST_DAYS;
        $this->maxOptimalDays = count($this->bestDays);
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
        $cacheItem    = $this->cacheProvider->getItem('contact.interactions.'.$contact->getId());
        if ($cacheItem->isHit()) {
            $interactions = $cacheItem->get();
        } else {
            $fetchInteractionsFromDate = $this->getCurrentDateTime($dateTimeZone)
                ->modify($this->fetchInteractionsFrom);
            $emailReads      = $this->getLeadStats($contact->getId(), $fetchInteractionsFromDate);
            $pageHits        = $this->getLeadHits($contact->getId(), $fetchInteractionsFromDate);
            $formSubmissions = $this->getFormSubmissions($contact->getId(), $fetchInteractionsFromDate);

            $emailReadInteractions = $this->processInteractions($emailReads, 'email.read', $dateTimeZone);
            $pageHitInteractions   = $this->processInteractions($pageHits, 'page.hit', $dateTimeZone);
            $formInteractions      = $this->processInteractions($formSubmissions, 'form.submit', $dateTimeZone);
            $interactions          = array_merge($emailReadInteractions, $pageHitInteractions, $formInteractions);

            $cacheItem->set($interactions);
            $cacheItem->expiresAfter($this->cacheTimeout * 60);
            $this->cacheProvider->save($cacheItem);
        }

        return $interactions;
    }

    /**
     * @param array<int, array<string, mixed>> $interactionsData
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \Exception
     */
    private function processInteractions(array $interactionsData, string $type, \DateTimeZone $dateTimeZone): array
    {
        $interactions           = [];
        $registeredInteractions = []; // Keep track of registered interactions to ensure one interaction type per hour

        foreach ($interactionsData as $interaction) {
            $dateKey = match ($type) {
                'email.read'  => 'dateRead',
                'page.hit'    => 'dateHit',
                'form.submit' => 'dateSubmitted',
                default       => throw new \Exception('Unhandled interaction type: '.$type),
            };
            $interactionDate = $interaction[$dateKey];
            $interactionDate->setTimezone($dateTimeZone);

            $interactionKey = $type.':'.$interactionDate->format('Y-m-d_H');
            if (!in_array($interactionKey, $registeredInteractions)) {
                $interactions[] = [
                    'type'      => $type,
                    'date'      => $interactionDate->format('Y-m-d H:i:s'),
                    'hourOfDay' => (int) $interactionDate->format(self::HOUR_FORMAT),
                    'dayOfWeek' => (int) $interactionDate->format(self::DAY_FORMAT),
                    'time'      => $interactionDate->format('H:i:s'),
                ];
                $registeredInteractions[] = $interactionKey;
            }
        }

        return $interactions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLeadStats(int $leadId, \DateTime $fromDate = null): array
    {
        return $this->statRepository->getLeadStats($leadId, [
            'order'        => ['timestamp', 'DESC'],
            'limit'        => $this->fetchLimit,
            'state'        => 'read',
            'basic_select' => true,
            'fromDate'     => $fromDate,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLeadHits(int $leadId, \DateTime $fromDate = null): array
    {
        return $this->hitRepository->getLeadHits($leadId, [
            'order'        => ['timestamp', 'DESC'],
            'limit'        => $this->fetchLimit,
            'fromDate'     => $fromDate,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFormSubmissions(int $leadId, \DateTime $fromDate = null): array
    {
        return $this->submissionRepository->getSubmissions([
            'leadId'       => $leadId,
            'order'        => ['timestamp', 'DESC'],
            'limit'        => $this->fetchLimit,
            'fromDate'     => $fromDate,
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
            throw new \Exception('Not enough elements to calculate optimal time');
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
     *
     * @throws \Exception
     */
    private function calculateOptimalDays(array $elements): array
    {
        if (0 === count($elements)) {
            throw new \Exception('Not enough elements to calculate optimal days');
        }

        // Count the frequency of each element.
        $frequency = array_count_values($elements);

        // Sort frequencies in descending order.
        arsort($frequency);

        // Get the elements sorted by frequency.
        $optimalDays = array_keys($frequency);

        // Return the top elements up to the max optimal days limit.
        return array_slice($optimalDays, 0, min($this->maxOptimalDays, count($optimalDays)));
    }
}
