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
    public function __construct(
        private Connection $db,
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
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
        $this->onDataCleanupForTable($event, $event->getDate(), 'email_stats', 'id', $this->translator->trans('mautic.maintenance.email_stats'));

        $compactionThresholdDays = (int) $this->coreParametersHelper->get('email_stats_compaction_threshold_days', 0);
        if ($compactionThresholdDays > 0) {
            // date_sent is always stored in UTC, so the threshold must be computed in UTC too,
            // independent of the request/session timezone.
            $thresholdDate = new \DateTime('-'.$compactionThresholdDays.' days', new \DateTimeZone('UTC'));
            $this->onDataCleanupForTable($event, $thresholdDate, 'email_stats_data', 'stat_id', $this->translator->trans('mautic.maintenance.email_stats_data'));
            $this->onDataCleanupForTable($event, $thresholdDate, 'email_stats_open_details', 'stat_id', $this->translator->trans('mautic.maintenance.email_stats_open_details'));
        }
    }

    private function onDataCleanupForTable(MaintenanceEvent $event, \DateTime $thresholdDate, string $tableName, string $idColumn, string $message): void
    {
        $qb = null;
        if ($event->isDryRun()) {
            $rows = $this->getCompactionCount($tableName, $idColumn, $thresholdDate, $qb);
        } else {
            $rows = $this->executeCompaction($tableName, $idColumn, $thresholdDate, $qb);
        }
        assert(null !== $qb);

        $event->setStat($message, $rows, $qb->getSQL(), $qb->getParameters());
    }

    private function getCompactionCount(string $tableName, string $idColumn, \DateTime $thresholdDate, ?QueryBuilder &$qb): int
    {
        $qb = $this->db->createQueryBuilder();

        return (int) $qb->select('count(s.'.$idColumn.') as records')
            ->from(MAUTIC_TABLE_PREFIX.$tableName, 's')
            ->where($qb->expr()->lte('s.date_sent', ':date'))
            ->setParameter('date', $thresholdDate->format('Y-m-d H:i:s'))
            ->executeQuery()
            ->fetchOne();
    }

    private function executeCompaction(string $tableName, string $idColumn, \DateTime $thresholdDate, ?QueryBuilder &$qb): int
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('s.'.$idColumn)
            ->from(MAUTIC_TABLE_PREFIX.$tableName, 's')
            ->where($qb->expr()->lte('s.date_sent', ':date'))
            ->setMaxResults(10000)
            ->setParameter('date', $thresholdDate->format('Y-m-d H:i:s'));

        $rows = 0;
        $qb2  = $this->db->createQueryBuilder();
        while (true) {
            $emailStatsIds = array_column($qb->executeQuery()->fetchAllAssociative(), $idColumn);
            if (!$emailStatsIds) {
                break;
            }
            foreach (array_chunk($emailStatsIds, 1000) as $emailStatsIdChunk) {
                $rows += $qb2->delete(MAUTIC_TABLE_PREFIX.$tableName)
                    ->where(
                        $qb2->expr()->in(
                            $idColumn, $emailStatsIdChunk
                        )
                    )->executeStatement();
            }
        }

        return $rows;
    }
}
