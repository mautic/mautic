<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

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
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 0],
        ];
    }

    /**
     * @param MaintenanceEvent $event
     */
    public function onDataCleanup(MaintenanceEvent $event)
    {
        $qb = $this->db->createQueryBuilder()
            ->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));

        if ($event->isDryRun()) {
            $qb->select('count(*) as records')
              ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
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
            $qb->select('l.id')->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
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

            $rows = 0;
            $qb->setMaxResults(10000)->setFirstResult(0);

            $qb2 = $this->db->createQueryBuilder();
            while (true) {
                $leadsIds = array_column($qb->execute()->fetchAll(), 'id');
                if (sizeof($leadsIds) === 0) {
                    break;
                }
                foreach ($leadsIds as $leadId) {
                    $rows += $qb2->delete(MAUTIC_TABLE_PREFIX.'leads')
                      ->where(
                        $qb2->expr()->eq(
                          'id', $leadId
                        )
                      )->execute();
                }
            }
        }

        $event->setStat($this->translator->trans('mautic.maintenance.visitors'), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
