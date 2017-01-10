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
            $rows = $qb->select('count(*) as records')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->lte('l.last_active', ':date'),
                        $qb->expr()->isNull('l.date_identified')
                    )
                )
                ->execute()
                ->fetchColumn();
        } else {
            $rows = $qb->delete(MAUTIC_TABLE_PREFIX.'leads')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->lte('last_active', ':date'),
                        $qb->expr()->isNull('date_identified')
                    )
                )
                ->execute();
        }

        $event->setStat($this->translator->trans('mautic.maintenance.visitors'), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
