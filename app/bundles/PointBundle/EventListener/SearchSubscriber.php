<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PointModel $pointModel,
        private TriggerModel $pointTriggerModel,
        private PointGroupModel $pointGroupModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH => [
                ['onGlobalSearchPointActions', 0],
                ['onGlobalSearchPointTriggers', 0],
                ['onGlobalSearchPointGroup', 0],
            ],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearchPointActions(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointModel,
            '@MauticPoint/SubscribedEvents/Search/global_point.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.point.actions.header.index', $results);
        }
    }

    public function onGlobalSearchPointGroup(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointGroupModel,
            '@MauticPoint/SubscribedEvents/Search/global_group.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.point.group.header.index', $results);
        }
    }

    public function onGlobalSearchPointTriggers(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointTriggerModel,
            '@MauticPoint/SubscribedEvents/Search/global_trigger.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.point.trigger.header.index', $results);
        }
    }

    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
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
