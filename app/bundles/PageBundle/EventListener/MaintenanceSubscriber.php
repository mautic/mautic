<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Connection $db, TranslatorInterface $translator)
    {
        $this->db         = $db;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 10], // Cleanup before visitors are processed
        ];
    }

    public function onDataCleanup(MaintenanceEvent $event)
    {
        $this->cleanData($event, 'page_hits');
        $this->cleanData($event, 'lead_utmtags');
    }

    private function cleanData(MaintenanceEvent $event, $table)
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
                  $qb->expr()->andX(
                    $qb->expr()->lte('l.date_added', ':date2'),
                    $qb->expr()->isNull('l.last_active')
                  ));
                $qb->setParameter('date2', $event->getDate()->format('Y-m-d H:i:s'));
            }

            $rows = $qb->execute()->fetchColumn();
        } else {
            $subQb = $this->db->createQueryBuilder();
            $subQb->select('id')->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
              ->where($qb->expr()->lte('l.last_active', ':date'));

            if (false === $event->isGdpr()) {
                $subQb->andWhere($qb->expr()->isNull('l.date_identified'));
            } else {
                $subQb->orWhere(
                  $subQb->expr()->andX(
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

                $leadsIds = array_column($subQb->execute()->fetchAll(), 'id');

                if (0 === sizeof($leadsIds)) {
                    break;
                }

                $rows += $qb->delete(MAUTIC_TABLE_PREFIX.$table)
                  ->where(
                    $qb->expr()->in(
                      'lead_id', $leadsIds
                    )
                  )
                  ->execute();
                ++$loop;
            }
        }
        $event->setStat($this->translator->trans('mautic.maintenance.'.$table), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
