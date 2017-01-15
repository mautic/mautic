<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\Model\TriggerModel;

/**
 * Class SearchSubscriber.
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var PointModel
     */
    protected $pointModel;

    /**
     * @var TriggerModel
     */
    protected $pointTriggerModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param PointModel   $pointModel
     * @param TriggerModel $pointTriggerModel
     */
    public function __construct(PointModel $pointModel, TriggerModel $pointTriggerModel)
    {
        $this->pointModel        = $pointModel;
        $this->pointTriggerModel = $pointTriggerModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->security->isGranted('point:points:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items = $this->pointModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);
            $pointCount = count($items);
            if ($pointCount > 0) {
                $pointsResults = [];
                $canEdit       = $this->security->isGranted('point:points:edit');
                foreach ($items as $item) {
                    $pointsResults[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_point.html.php',
                        [
                            'item'    => $item,
                            'canEdit' => $canEdit,
                        ]
                    )->getContent();
                }
                if ($pointCount > 5) {
                    $pointsResults[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_point.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($pointCount - 5),
                        ]
                    )->getContent();
                }
                $pointsResults['count'] = $pointCount;
                $event->addResults('mautic.point.actions.header.index', $pointsResults);
            }
        }

        if ($this->security->isGranted('point:triggers:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items = $this->pointTriggerModel->getEntities(
                [
                    'limit'  => 5,
                    'filter' => $str,
                ]);
            $count = count($items);
            if ($count > 0) {
                $results = [];
                $canEdit = $this->security->isGranted('point:triggers:edit');
                foreach ($items as $item) {
                    $results[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_trigger.html.php',
                        [
                            'item'    => $item,
                            'canEdit' => $canEdit,
                        ]
                    )->getContent();
                }
                if ($count > 5) {
                    $results[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_trigger.html.php',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5),
                        ]
                    )->getContent();
                }
                $results['count'] = $count;
                $event->addResults('mautic.point.trigger.header.index', $results);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $security = $this->security;
        if ($security->isGranted('point:points:view')) {
            $event->addCommands(
                'mautic.point.actions.header.index',
                $this->pointModel->getCommandList()
            );
        }
    }
}
