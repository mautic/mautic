<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\PointBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH      => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST => array('onBuildCommandList', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch (MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->security->isGranted('point:points:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items      = $this->factory->getModel('point')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            $pointCount = count($items);
            if ($pointCount > 0) {
                $pointsResults = array();
                $canEdit       = $this->security->isGranted('point:points:edit');
                foreach ($items as $item) {
                    $pointsResults[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_point.html.php',
                        array(
                            'item'    => $item,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if ($pointCount > 5) {
                    $pointsResults[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_point.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($pointCount - 5)
                        )
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

            $items = $this->factory->getModel('point.trigger')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            $count = count($items);
            if ($count > 0) {
                $results = array();
                $canEdit = $this->security->isGranted('point:triggers:edit');
                foreach ($items as $item) {
                    $results[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_trigger.html.php',
                        array(
                            'item'    => $item,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if ($count > 5) {
                    $results[] = $this->templating->renderResponse(
                        'MauticPointBundle:SubscribedEvents\Search:global_trigger.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5)
                        )
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
    public function onBuildCommandList (MauticEvents\CommandListEvent $event)
    {
        $security = $this->security;
        if ($security->isGranted('point:points:view')) {
            $event->addCommands(
                'mautic.point.actions.header.index',
                $this->factory->getModel('point')->getCommandList()
            );
        }
    }
}