<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class MaintenanceSubscriber
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
     * @param MauticFactory $factory
     * @param Connection    $db
     */
    public function __construct(MauticFactory $factory, Connection $db)
    {
        parent::__construct($factory);

        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents ()
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 10] // Cleanup before visitors are processed
        ];
    }

    /**
     * @param MaintenanceEvent $event
     */
    public function onDataCleanup (MaintenanceEvent $event)
    {
        $qb = $this->db->createQueryBuilder()
            ->setParameter('date', $event->getDate()->format('Y-m-d H:i:s'));

        if ($event->isDryRun()) {
            $rows = $qb->select('count(*) as records')
                ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
                ->join('h', MAUTIC_TABLE_PREFIX.'leads', 'l', 'h.lead_id = l.id')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->lte('l.last_active', ':date'),
                        $qb->expr()->isNull('l.date_identified')
                    )
                )
                ->execute()
                ->fetchColumn();
        } else {
            $subQb = $this->db->createQueryBuilder();
            $subQb->select('id')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->lte('l.last_active', ':date'),
                        $qb->expr()->isNull('l.date_identified')
                    )
                );

            $rows = $qb->delete(MAUTIC_TABLE_PREFIX.'page_hits')
                ->where(
                    $qb->expr()->in(
                        'lead_id',
                        $subQb->getSQL()
                    )
                )
                ->execute();
        }

        $event->setStat($this->translator->trans('mautic.maintenance.visitor_page_hits'), $rows, $qb->getSQL(), $qb->getParameters());
    }
}
