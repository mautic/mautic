<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Helper;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class SyncDateHelper
{
    private ?\DateTimeInterface $syncFromDateTime = null;

    private ?\DateTimeInterface $syncToDateTime = null;

    private ?\DateTimeImmutable $syncDateTime = null;

    /**
     * @var \DateTimeInterface[]
     */
    private array $lastObjectSyncDates = [];

    private ?\DateTimeInterface $internalSyncStartDateTime = null;

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function setSyncDateTimes(?\DateTimeInterface $fromDateTime = null, ?\DateTimeInterface $toDateTime = null): void
    {
        $this->syncFromDateTime    = $fromDateTime;
        $this->syncToDateTime      = $toDateTime;
        $this->syncDateTime        = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->lastObjectSyncDates = [];
    }

    public function getSyncFromDateTime(string $integration, string $object): \DateTimeInterface
    {
        if ($this->syncFromDateTime) {
            // The command requested a specific start date so use it

            return $this->syncFromDateTime;
        }

        $key = $integration.$object;
        if (isset($this->lastObjectSyncDates[$key])) {
            // Use the same sync date for integrations to paginate properly

            return $this->lastObjectSyncDates[$key];
        }

        if (MauticSyncDataExchange::NAME !== $integration && $lastSync = $this->getLastSyncDateForObject($integration, $object)) {
            // Use the latest sync date recorded
            $this->lastObjectSyncDates[$key] = $lastSync;
        } else {
            // Otherwise, just sync the last 24 hours
            $this->lastObjectSyncDates[$key] = new \DateTimeImmutable('-24 hours', new \DateTimeZone('UTC'));
        }

        return $this->lastObjectSyncDates[$key];
    }

    public function getSyncToDateTime(): ?\DateTimeInterface
    {
        if ($this->syncToDateTime) {
            return $this->syncToDateTime;
        }

        return $this->syncDateTime;
    }

    public function getSyncDateTime(): ?\DateTimeInterface
    {
        return $this->syncDateTime;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastSyncDateForObject(string $integration, string $object): ?\DateTimeInterface
    {
        $qb = $this->connection->createQueryBuilder();

        $result = $qb
            ->select('max(m.last_sync_date)')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'm')
            ->where(
                $qb->expr()->eq('m.integration', ':integration'),
                $qb->expr()->eq('m.integration_object_name', ':object')
            )
            ->setParameter('integration', $integration)
            ->setParameter('object', $object)
            ->executeQuery()
            ->fetchOne();

        if (!$result) {
            return null;
        }

        $lastSync = new \DateTimeImmutable($result, new \DateTimeZone('UTC'));

        // The last sync is out of the requested sync date/time range
        if ($this->syncFromDateTime && $lastSync < $this->syncFromDateTime) {
            return null;
        }

        // The last sync is out of the requested sync date/time range
        if ($lastSync > $this->getSyncToDateTime()) {
            return null;
        }

        return $lastSync;
    }

    public function getInternalSyncStartDateTime(): ?\DateTimeInterface
    {
        return $this->internalSyncStartDateTime;
    }

    public function setInternalSyncStartDateTime(): void
    {
        if ($this->internalSyncStartDateTime) {
            return;
        }

        $this->internalSyncStartDateTime = $this->calculateInternalSyncStartDateTime();
    }

    private function calculateInternalSyncStartDateTime(): \DateTimeInterface
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        // If there is no syncToDateTime value use "now"
        if (!$this->getSyncToDateTime()) {
            return $now;
        }

        // Clone it so that we don't modify the initial object
        $syncToDateTime = clone $this->getSyncToDateTime();

        // We should compare in UTC timezone
        if (method_exists($syncToDateTime, 'setTimezone')) {
            $syncToDateTime->setTimezone(new \DateTimeZone('UTC'));
        }

        // If syncToDate is less than now then use syncToDate, because otherwise we may delete
        // changes that aren't supposed to be deleted from the sync_object_field_change_report table
        return min($now, $syncToDateTime);
    }
}
