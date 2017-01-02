<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Event\StatsEvent;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonSubscriber
{
    /**
     * @var array of CommonRepository
     */
    protected $repositories = [];

    /**
     * @var null
     */
    protected $selects = null;

    /**
     * StatsSubscriber constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->repositories[] = $em->getRepository('MauticCoreBundle:AuditLog');
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::LIST_STATS => ['onStatsFetch', 0],
        ];
    }

    /**
     * @param StatsEvent $event
     */
    public function onStatsFetch(StatsEvent $event)
    {
        /** @var CommonRepository $repository */
        foreach ($this->repositories as $repository) {
            $table = $repository->getTableName();
            if ($event->isLookingForTable($table)) {
                $select = (isset($this->selects[$table])) ? $this->selects[$table] : null;
                $event->setSelect($select)
                      ->setRepository($repository);
            }
        }
    }
}
