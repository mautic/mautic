<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonSubscriber
{
    /**
     * @var AssetModel
     */
    protected $model;

    /**
     * StatsSubscriber constructor.
     *
     * @param AssetModel $model
     */
    public function __construct(AssetModel $model)
    {
        $this->model = $model;
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
        if ($event->isLookingForTable('asset_downloads')) {
            $event->setResults(
                $this->model->getDownloadRepository()->getRows(
                    $event->getStart(),
                    $event->getLimit(),
                    $event->getOrder(),
                    $event->getWhere()
                )
            );
        }
    }
}
