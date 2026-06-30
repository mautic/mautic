<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $db,
        private readonly UserTokenRepositoryInterface $userTokenRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', -50],
        ];
    }

    public function onDataCleanup(MaintenanceEvent $event): void
    {
        $this->cleanupData($event, 'audit_log');
        $this->cleanupData($event, 'notifications');

        $rows = $this->userTokenRepository->deleteExpired($event->isDryRun());
        $event->setStat($this->translator->trans('mautic.maintenance.user_tokens'), $rows);
    }

    private function cleanupData(MaintenanceEvent $event, string $table): void
    {
        $qb = $this->db->createQueryBuilder()
            ->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));

        if ($event->isDryRun()) {
            $rows = (int) $qb->select('count(*) as records')
                ->from(MAUTIC_TABLE_PREFIX.$table, 'log')
                ->where(
                    $qb->expr()->lte('log.date_added', ':date')
                )
                ->executeQuery()
                ->fetchOne();
        } else {
            $qb->select('log.id')
              ->from(MAUTIC_TABLE_PREFIX.$table, 'log')
              ->where(
                  $qb->expr()->lte('log.date_added', ':date')
              );

            $rows = 0;
            $qb->setMaxResults(10000)->setFirstResult(0);

            $qb2 = $this->db->createQueryBuilder();
            while (true) {
                $ids = array_column($qb->executeQuery()->fetchAllAssociative(), 'id');

                if (0 === sizeof($ids)) {
                    break;
                }

                $rows += $qb2->delete(MAUTIC_TABLE_PREFIX.$table)
                  ->where(
                      $qb2->expr()->in(
                          'id', ':ids'
                      )
                  )
                  ->setParameter('ids', array_map('intval', $ids), ArrayParameterType::INTEGER)
                  ->executeStatement();
            }
        }

        $event->setStat($this->translator->trans('mautic.maintenance.'.$table), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
