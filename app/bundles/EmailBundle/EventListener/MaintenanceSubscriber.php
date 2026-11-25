<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\Query\QueryBuilder;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatData;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MaintenanceSubscriber
{
    private const int SELECT_BATCH_SIZE = 10000;

    private const int CHANGE_CHUNK_SIZE = 1000;

    /**
     * ArrayType's value for null, which marks a legacy tokens column holding nothing.
     */
    private const string SERIALIZED_NULL = 'N;';

    public function __construct(
        private Connection $db,
        private TranslatorInterface $translator,
        private EmailConfigInterface $emailConfig,
    ) {
    }

    #[AsEventListener(
        event: MaintenanceEvent::class,
    )]
    public function onDataCleanup(MaintenanceEvent $event): void
    {
        // Only stored personalisation is removed; email_stats rows feed sends, segments, campaigns and reports
        $days = $this->emailConfig->getStatsCompactionThresholdDays();
        if (0 === $days) {
            return;
        }

        // date_sent is stored in UTC
        $threshold = new \DateTimeImmutable('-'.$days.' days', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $this->removeDataRows($event, $threshold);
        $this->clearLegacyTokens($event, $threshold);
    }

    private function removeDataRows(MaintenanceEvent $event, string $threshold): void
    {
        $qb = $this->db->createQueryBuilder();
        $qb->from(MAUTIC_TABLE_PREFIX.StatData::TABLE_NAME, 'sd')
            ->where($qb->expr()->lte('sd.date_sent', ':date'))
            ->setParameter('date', $threshold);

        if ($event->isDryRun()) {
            $rows = (int) (clone $qb)->select('count(sd.stat_id)')->executeQuery()->fetchOne();
        } else {
            $rows = 0;
            $qb->select('sd.stat_id')->setMaxResults(self::SELECT_BATCH_SIZE);
            while ($ids = $qb->executeQuery()->fetchFirstColumn()) {
                foreach (array_chunk($ids, self::CHANGE_CHUNK_SIZE) as $idChunk) {
                    $rows += $this->db->createQueryBuilder()
                        ->delete(MAUTIC_TABLE_PREFIX.StatData::TABLE_NAME)
                        ->where('stat_id IN (:ids)')
                        ->setParameter('ids', $idChunk, ArrayParameterType::INTEGER)
                        ->executeStatement();
                }
            }
        }

        $event->setStat($this->translator->trans('mautic.maintenance.email_stats_data'), $rows, $qb->getSQL(), $qb->getParameters());
    }

    private function clearLegacyTokens(MaintenanceEvent $event, string $threshold): void
    {
        $table = MAUTIC_TABLE_PREFIX.Stat::TABLE_NAME;

        // Bounds the id scan to the old stats, found through the date_sent index
        $lastOldId = (int) $this->db->createQueryBuilder()
            ->select('MAX(es.id)')
            ->from($table, 'es')
            ->where('es.date_sent <= :date')
            ->setParameter('date', $threshold)
            ->executeQuery()
            ->fetchOne();

        $qb = $this->createLegacyTokensQuery($threshold, $lastOldId);

        if ($event->isDryRun()) {
            $rows = (int) (clone $qb)->select('count(es.id)')->executeQuery()->fetchOne();
        } else {
            $rows = 0;
            $qb->select('es.id')
                ->andWhere('es.id > :afterId')
                ->orderBy('es.id')
                ->setMaxResults(self::SELECT_BATCH_SIZE)
                ->setParameter('afterId', 0);
            while ($ids = $qb->executeQuery()->fetchFirstColumn()) {
                foreach (array_chunk($ids, self::CHANGE_CHUNK_SIZE) as $idChunk) {
                    $rows += $this->db->createQueryBuilder()
                        ->update($table)
                        ->set('tokens', 'NULL')
                        ->where('id IN (:ids)')
                        ->setParameter('ids', $idChunk, ArrayParameterType::INTEGER)
                        ->executeStatement();
                }
                $qb->setParameter('afterId', (int) end($ids));
            }
        }

        $event->setStat($this->translator->trans('mautic.maintenance.email_stats_tokens'), $rows, $qb->getSQL(), $qb->getParameters());
    }

    private function createLegacyTokensQuery(string $threshold, int $lastOldId): QueryBuilder
    {
        $qb = $this->db->createQueryBuilder();

        return $qb->from(MAUTIC_TABLE_PREFIX.Stat::TABLE_NAME, 'es')
            ->where($qb->expr()->lte('es.id', ':lastOldId'))
            ->andWhere($qb->expr()->lte('es.date_sent', ':date'))
            ->andWhere($qb->expr()->isNotNull('es.tokens'))
            ->andWhere($qb->expr()->neq('es.tokens', ':serializedNull'))
            ->setParameter('lastOldId', $lastOldId)
            ->setParameter('date', $threshold)
            ->setParameter('serializedNull', self::SERIALIZED_NULL);
    }
}
