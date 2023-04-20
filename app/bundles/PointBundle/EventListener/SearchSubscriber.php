<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class SearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var PointModel
     */
    private $pointModel;

    /**
     * @var TriggerModel
     */
    private $pointTriggerModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        PointModel $pointModel,
        TriggerModel $pointTriggerModel,
        CorePermissions $security,
        Environment $twig
    ) {
        $this->pointModel        = $pointModel;
        $this->pointTriggerModel = $pointTriggerModel;
        $this->security          = $security;
        $this->twig              = $twig;
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
                    $pointsResults[] = $this->twig->render(
                        '@MauticPoint/SubscribedEvents/Search/global_point.html.twig',
                        [
                            'item'    => $item,
                            'canEdit' => $canEdit,
                        ]
                    );
                }
                if ($pointCount > 5) {
                    $pointsResults[] = $this->twig->render(
                        '@MauticPoint/SubscribedEvents/Search/global_point.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($pointCount - 5),
                        ]
                    );
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
                    $results[] = $this->twig->render(
                        '@MauticPoint/SubscribedEvents/Search/global_trigger.html.twig',
                        [
                            'item'    => $item,
                            'canEdit' => $canEdit,
                        ]
                    );
                }
                if ($count > 5) {
                    $results[] = $this->twig->render(
                        '@MauticPoint/SubscribedEvents/Search/global_trigger.html.twig',
                        [
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5),
                        ]
                    );
                }
                $results['count'] = $count;
                $event->addResults('mautic.point.trigger.header.index', $results);
            }
        }
    }

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
