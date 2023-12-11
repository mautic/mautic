<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $db,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 10], // Cleanup before visitors are processed
        ];
    }

    public function onDataCleanup(MaintenanceEvent $event): void
    {
        $this->cleanData($event, 'page_hits');
        $this->cleanData($event, 'lead_utmtags');
    }

    private function cleanData(MaintenanceEvent $event, $table): void
    {
        $qb = $this->db->createQueryBuilder()
            ->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));

        if ($event->isDryRun()) {
            $qb->select('count(*) as records')
              ->from(MAUTIC_TABLE_PREFIX.$table, 'h')
              ->join('h', MAUTIC_TABLE_PREFIX.'leads', 'l', 'h.lead_id = l.id')
              ->where($qb->expr()->lte('l.last_active', ':date'));

            if (false === $event->isGdpr()) {
                $qb->andWhere($qb->expr()->isNull('l.date_identified'));
            } else {
                $qb->orWhere(
                    $qb->expr()->and(
                        $qb->expr()->lte('l.date_added', ':date2'),
                        $qb->expr()->isNull('l.last_active')
                    ));
                $qb->setParameter('date2', $event->getDate()->format('Y-m-d H:i:s'));
            }

            $rows = $qb->executeQuery()->fetchOne();
        } else {
            $subQb = $this->db->createQueryBuilder();
            $subQb->select('id')->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
              ->where($qb->expr()->lte('l.last_active', ':date'));

            if (false === $event->isGdpr()) {
                $subQb->andWhere($qb->expr()->isNull('l.date_identified'));
            } else {
                $subQb->orWhere(
                    $subQb->expr()->and(
                        $subQb->expr()->lte('l.date_added', ':date2'),
                        $subQb->expr()->isNull('l.last_active')
                    ));
                $subQb->setParameter('date2', $event->getDate()->format('Y-m-d H:i:s'));
            }
            $rows = 0;
            $loop = 0;
            $subQb->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));
            while (true) {
                $subQb->setMaxResults(10000)->setFirstResult($loop * 10000);

                $leadsIds = array_column($subQb->executeQuery()->fetchAllAssociative(), 'id');

                if (0 === sizeof($leadsIds)) {
                    break;
                }

                $rows += $qb->delete(MAUTIC_TABLE_PREFIX.$table)
                  ->where(
                      $qb->expr()->in(
                          'lead_id', $leadsIds
                      )
                  )
                  ->executeStatement();
                ++$loop;
            }
        }
        $event->setStat($this->translator->trans('mautic.maintenance.'.$table), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
