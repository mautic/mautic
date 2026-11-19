<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    private const DELETE_CHUNK_SIZE = 1000;

    public function __construct(
        private readonly Connection $db,
        private readonly TranslatorInterface $translator,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 0],
        ];
    }

    public function onDataCleanup(MaintenanceEvent $event): void
    {
        // email_stats rows are never deleted: sends, segment filters, campaign and report totals all count them.
        $compactionThresholdDays = (int) $this->coreParametersHelper->get('email_stats_compaction_threshold_days', 0);
        if ($compactionThresholdDays > 0) {
            // date_sent is always stored in UTC, so the threshold must be computed in UTC too,
            // independent of the request/session timezone.
            $thresholdDate = new \DateTime('-'.$compactionThresholdDays.' days', new \DateTimeZone('UTC'));
            $this->onDataCleanupForTable($event, $thresholdDate, 'email_stats_data', 'stat_id', $this->translator->trans('mautic.maintenance.email_stats_data'));
            $this->onDataCleanupForTable($event, $thresholdDate, 'email_stats_open_details', 'id', $this->translator->trans('mautic.maintenance.email_stats_open_details'));
        }
    }

    private function onDataCleanupForTable(MaintenanceEvent $event, \DateTimeInterface $thresholdDate, string $tableName, string $idColumn, string $message): void
    {
        $qb = $this->createCleanupQueryBuilder($tableName, $thresholdDate);
        if ($event->isDryRun()) {
            $rows = $this->getCompactionCount($qb, $idColumn);
        } else {
            $rows = $this->executeCompaction($qb, $tableName, $idColumn);
        }

        $event->setStat($message, $rows, $qb->getSQL(), $qb->getParameters());
    }

    private function createCleanupQueryBuilder(string $tableName, \DateTimeInterface $thresholdDate): QueryBuilder
    {
        $qb = $this->db->createQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.$tableName, 's')
            ->where($qb->expr()->lte('s.date_sent', ':date'))
            ->setParameter('date', $thresholdDate->format('Y-m-d H:i:s'));

        return $qb;
    }

    private function getCompactionCount(QueryBuilder $qb, string $idColumn): int
    {
        return (int) $qb->select('count(s.'.$idColumn.') as records')
            ->executeQuery()
            ->fetchOne();
    }

    private function executeCompaction(QueryBuilder $qb, string $tableName, string $idColumn): int
    {
        $qb->select('s.'.$idColumn)
            ->setMaxResults(10000);

        $rows = 0;
        $qb2  = $this->db->createQueryBuilder();
        while (true) {
            $ids = array_column($qb->executeQuery()->fetchAllAssociative(), $idColumn);
            if (!$ids) {
                break;
            }
            foreach (array_chunk($ids, self::DELETE_CHUNK_SIZE) as $idChunk) {
                $rows += $qb2->delete(MAUTIC_TABLE_PREFIX.$tableName)
                    ->where(
                        $qb2->expr()->in(
                            $idColumn, $idChunk
                        )
                    )->executeStatement();
            }
        }

        return $rows;
    }
}
