<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\StatEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GraphAggregateStatsSubscriber implements EventSubscriberInterface
{
    /**
     * @var StatsCollectionHelper
     */
    private $statsCollectionHelper;

    /**
     * GraphAggregateStatsSubscriber constructor.
     */
    public function __construct(StatsCollectionHelper $statsCollectionHelper)
    {
        $this->statsCollectionHelper = $statsCollectionHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            StatEvents::AGGREGATE_STAT_REQUEST => ['onStatRequest', 0],
        ];
    }

    public function onStatRequest(AggregateStatRequestEvent $event)
    {
        if (!$event->checkContextPrefix(StatsCollectionHelper::GENERAL_STAT_PREFIX.'-')) {
            return;
        }

        $this->statsCollectionHelper->generateStats(
            $event->getStatName(),
            $event->getFromDateTime(),
            $event->getToDateTime(),
            $event->getOptions(),
            $event->getStatCollection()
        );

        $event->statsCollected();
    }
}
