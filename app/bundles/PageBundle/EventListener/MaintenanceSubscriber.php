<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class MaintenanceSubscriber.
 */
class MaintenanceSubscriber extends CommonSubscriber
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * MaintenanceSubscriber constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
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

    /**
     * @param MaintenanceEvent $event
     */
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

            if ($event->isGdpr() === false) {
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

            if ($event->isGdpr() === false) {
                $subQb->andWhere($qb->expr()->isNull('l.date_identified'));
            } else {
                $subQb->orWhere(
                  $subQb->expr()->andX(
                    $subQb->expr()->lte('l.date_added', ':date2'),
                    $subQb->expr()->isNull('l.last_active')
                  ));
                $qb->setParameter('date2', $event->getDate()->format('Y-m-d H:i:s'));
            }
            $rows = 0;
            $loop = 0;
            $subQb->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));
            while (true) {
                $subQb->setMaxResults(10000)->setFirstResult($loop * 10000);

                $leadsIds = array_column($subQb->execute()->fetchAll(), 'id');

                if (sizeof($leadsIds) === 0) {
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
