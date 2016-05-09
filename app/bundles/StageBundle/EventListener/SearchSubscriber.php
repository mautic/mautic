<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\StageBundle\EventListener
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
        if ($this->security->isGranted('stage:stages:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items      = $this->factory->getModel('stage')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            $stageCount = count($items);
            if ($stageCount > 0) {
                $stagesResults = array();
                $canEdit       = $this->security->isGranted('stage:stages:edit');
                foreach ($items as $item) {
                    $stagesResults[] = $this->templating->renderResponse(
                        'MauticStageBundle:SubscribedEvents\Search:global_stage.html.php',
                        array(
                            'item'    => $item,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if ($stageCount > 5) {
                    $stagesResults[] = $this->templating->renderResponse(
                        'MauticStageBundle:SubscribedEvents\Search:global_stage.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($stageCount - 5)
                        )
                    )->getContent();
                }
                $stagesResults['count'] = $stageCount;
                $event->addResults('mautic.stage.actions.header.index', $stagesResults);
            }
        }

        if ($this->security->isGranted('stage:triggers:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $items = $this->factory->getModel('stage.trigger')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            $count = count($items);
            if ($count > 0) {
                $results = array();
                $canEdit = $this->security->isGranted('stage:triggers:edit');
                foreach ($items as $item) {
                    $results[] = $this->templating->renderResponse(
                        'MauticStageBundle:SubscribedEvents\Search:global_trigger.html.php',
                        array(
                            'item'    => $item,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if ($count > 5) {
                    $results[] = $this->templating->renderResponse(
                        'MauticStageBundle:SubscribedEvents\Search:global_trigger.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5)
                        )
                    )->getContent();
                }
                $results['count'] = $count;
                $event->addResults('mautic.stage.trigger.header.index', $results);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList (MauticEvents\CommandListEvent $event)
    {
        $security = $this->security;
        if ($security->isGranted('stage:stages:view')) {
            $event->addCommands(
                'mautic.stage.actions.header.index',
                $this->factory->getModel('stage')->getCommandList()
            );
        }
    }
}