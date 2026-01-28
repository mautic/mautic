<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Mautic\CampaignBundle\DTO\PublishState;
use Mautic\CampaignBundle\DTO\PublishStateDateRange;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;

class PublishStateService
{
    /**
     * @var array<int, PublishStateDateRange[]>
     */
    private array $cachedRangesByCampaign = [];

    public function __construct(private AuditLogRepository $auditLogRepository)
    {
    }

    public function getUnublishedSecondsSince(Campaign $campaign, \DateTimeInterface $eventLogCreatedDate): int
    {
        $unpublishDateRanges = $this->generateUnpublishDateRanges($campaign);
        $lastPublishDate     = $this->getLastPublishDate($campaign);

        if (!$lastPublishDate) {
            return 0; // The campaign is not published, so nothing to count
        }

        $unpublishedSeconds = 0;

        foreach ($unpublishDateRanges as $range) {
            if (!$range->getToDate()) {
                continue;
            }
            if ($range->getFromDate() <= $eventLogCreatedDate && $range->getToDate() >= $eventLogCreatedDate) {
                $unpublishedSeconds += (int) $range->getToDate()->getTimestamp() - (int) $eventLogCreatedDate->getTimestamp();
                continue;
            }
            if ($range->getFromDate() >= $eventLogCreatedDate) {
                $unpublishedSeconds += (int) $range->getToDate()->getTimestamp() - (int) $range->getFromDate()->getTimestamp();
            }
        }

        return $unpublishedSeconds;
    }

    /**
     * @return PublishStateDateRange[]
     */
    public function generateUnpublishDateRanges(Campaign $campaign): array
    {
        return $this->filterRangesForState(
            $this->generatePublishStateDateRanges($campaign),
            false
        );
    }

    /**
     * Returns the last publish date of the campaign.
     *
     * @return \DateTimeInterface|null where null means the campaign is not published
     */
    public function getLastPublishDate(Campaign $campaign): ?\DateTimeInterface
    {
        $eventLogCreatedDateRanges        = $this->generatePublishStateDateRanges($campaign);
        $publishOnlyStates                = $this->filterRangesForState($eventLogCreatedDateRanges, true);

        if (empty($publishOnlyStates)) {
            return null; // The campaign has never been published
        }

        return end($publishOnlyStates)->getFromDate();
    }

    /**
     * @param PublishStateDateRange[] $ranges
     *
     * @return PublishStateDateRange[]
     */
    private function filterRangesForState(array $ranges, bool $published): array
    {
        return array_values(
            array_filter(
                $ranges,
                static fn (PublishStateDateRange $range) => $range->getPublished() === $published
            )
        );
    }

    /**
     * @return PublishStateDateRange[]
     */
    private function generatePublishStateDateRanges(Campaign $campaign): array
    {
        if (!isset($this->cachedRangesByCampaign[$campaign->getId()])) {
            $rawAuditLogs        = $this->getAuditLogsForCampaign($campaign);
            $publishStates       = $this->buildPublishStates($rawAuditLogs, $campaign->getIsPublished());
            $manualPublishRanges = $this->buildManualPublishDateRanges($publishStates);
            $refinedRanges       = $this->refinePublishDateRanges($manualPublishRanges, $publishStates);

            $this->cachedRangesByCampaign[$campaign->getId()] = $this->mergeConsecutiveRanges($refinedRanges);
        }

        return $this->cachedRangesByCampaign[$campaign->getId()];
    }

    /**
     * Merge ranges that are next to each other and have the same state.
     *
     * @param PublishStateDateRange[] $ranges
     *
     * @return PublishStateDateRange[]
     */
    private function mergeConsecutiveRanges(array $ranges): array
    {
        $mergedRanges = [];
        $currentState = null;
        $currentRange = null;
        foreach ($ranges as $range) {
            if (null === $currentRange) {
                $currentRange   = $range;
                $currentState   = $range->getPublished();
                $mergedRanges[] = $currentRange;
                continue;
            }

            if ($currentState === $range->getPublished()) {
                // Merge with the current range
                $currentRange->setToDate($range->getToDate());
                continue;
            }

            // Save the current range and start a new one
            $currentRange   = $range;
            $currentState   = $range->getPublished();
            $mergedRanges[] = $currentRange;
        }

        return $mergedRanges;
    }

    /**
     * Takes the hard publish ranges and splits them into refined ranges based on the publish up and down dates by the publish states.
     *
     * @param PublishStateDateRange[] $manualPublishRanges
     * @param PublishState[]          $publishStates
     *
     * @return PublishStateDateRange[]
     */
    private function refinePublishDateRanges(array $manualPublishRanges, array $publishStates): array
    {
        $refinedRanges = [];

        // Now we need to go through the published only states and update them with the publish up and down dates.
        foreach ($manualPublishRanges as $manualPublishRange) {
            if (!$manualPublishRange->getPublished()) {
                $refinedRanges[$manualPublishRange->getFromDate()->getTimestamp()] = $manualPublishRange;
                continue; // Skip if the range is not published
            }
            foreach ($publishStates as $publishState) {
                if (!$publishState->getPublished()) {
                    // Already added above
                    continue;
                }

                if (!$manualPublishRange->happenedWithinRange($publishState->getDateAdded())) {
                    // We want to split a date range only if the state change happened within the range.
                    continue;
                }

                if (!$publishState->getPublishUp() && !$publishState->getPublishDown()) {
                    // No publish up or down date set, so this is an uninterrupted published state.
                    $refinedRanges[$publishState->getDateAdded()->getTimestamp()] = new PublishStateDateRange(true, $publishState->getDateAdded(), $manualPublishRange->getToDate());
                    continue;
                }

                $publishUp   = null;
                $publishDown = null;

                if ($publishState->getPublishDown() && $manualPublishRange->happenedWithinRange($publishState->getPublishDown())) {
                    $publishDown = $publishState->getPublishDown();
                }

                if ($publishState->getPublishUp() && $manualPublishRange->happenedWithinRange($publishState->getPublishUp())) {
                    $publishUp = $publishState->getPublishUp();
                }

                if ($publishUp && $publishDown) {
                    $refinedRanges[$manualPublishRange->getFromDate()->getTimestamp()] = new PublishStateDateRange(false, $manualPublishRange->getFromDate(), $publishState->getPublishUp());
                    $refinedRanges[$publishState->getPublishUp()->getTimestamp()]      = new PublishStateDateRange(true, $publishState->getPublishUp(), $publishDown);
                    $refinedRanges[$publishState->getPublishDown()->getTimestamp()]    = new PublishStateDateRange(false, $publishState->getPublishDown(), $manualPublishRange->getToDate());
                }

                if ($publishUp && !$publishDown) {
                    $refinedRanges[$manualPublishRange->getFromDate()->getTimestamp()] = new PublishStateDateRange(false, $manualPublishRange->getFromDate(), $publishUp);
                    $refinedRanges[$publishUp->getTimestamp()]                         = new PublishStateDateRange(true, $publishUp, $manualPublishRange->getToDate());
                }

                if (!$publishUp && $publishDown) {
                    $refinedRanges[$manualPublishRange->getFromDate()->getTimestamp()] = new PublishStateDateRange(true, $manualPublishRange->getFromDate(), $publishDown);
                    $refinedRanges[$publishDown->getTimestamp()]                       = new PublishStateDateRange(false, $publishDown, $manualPublishRange->getToDate());
                }
            }
        }

        return array_values($refinedRanges);
    }

    /**
     * Takes the publish states and builds a timeline of when a user manually changed the publish state.
     * It does not count with publish up and down dates, only with the manual changes.
     *
     * @param PublishState[] $publishStates
     *
     * @return PublishStateDateRange[]
     */
    private function buildManualPublishDateRanges(array $publishStates): array
    {
        /** @var PublishStateDateRange[] */
        $manualPublishRanges = [];
        $currentRange        = null;
        $currentState        = null;

        // At first build a timeline of only manually set publish state changes without publish up and down dates.
        foreach ($publishStates as $publishState) {
            if (null === $currentRange) {
                $currentRange          = new PublishStateDateRange($publishState->getPublished(), $publishState->getDateAdded());
                $currentState          = $publishState->getPublished();
                $manualPublishRanges[] = $currentRange;
                continue;
            }

            // Always set the to date of the current (previous) range.
            $currentRange->setToDate($publishState->getDateAdded());

            if ($currentState === $publishState->getPublished() && false === $publishState->getPublished()) {
                // Merge with the current range if unpublished
                continue;
            }

            // Save the current range and start a new one
            $currentRange          = new PublishStateDateRange($publishState->getPublished(), $publishState->getDateAdded());
            $currentState          = $publishState->getPublished();
            $manualPublishRanges[] = $currentRange;
        }

        return $manualPublishRanges;
    }

    /**
     * Takes the raw audit logs for a campaign, simplifies them to a simple DTO objects
     * and remembers the states of the previous states to get the full picture.
     *
     * @param Collection<int,AuditLog> $rawAuditLogs
     *
     * @return PublishState[]
     */
    private function buildPublishStates(Collection $rawAuditLogs, bool $defaultPublishState): array
    {
        $publishStates = [];

        // Build better structure of publish states that hold publish date values from previous states.
        foreach ($rawAuditLogs as $log) {
            if (count($publishStates)) {
                $publishState = clone $publishStates[array_key_last($publishStates)];
            } else {
                $publishState = new PublishState();
            }

            $publishState->setFromAuditLog($log, $defaultPublishState);

            $publishStates[] = $publishState;
        }

        return $publishStates;
    }

    /**
     * @return Collection<int, AuditLog>
     */
    private function getAuditLogsForCampaign(Campaign $campaign): Collection
    {
        /** @var Collection<int, AuditLog> $result */
        $result = $this->auditLogRepository->matching(
            new Criteria(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('object', 'campaign'),
                    Criteria::expr()->eq('objectId', $campaign->getId()),
                ),
                ['id' => Order::Ascending]
            )
        );

        return $result;
    }
}
