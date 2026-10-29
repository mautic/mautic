<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SearchSubscriber
{
    public function __construct(
        private PointModel $pointModel,
        private TriggerModel $pointTriggerModel,
        private PointGroupModel $pointGroupModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchPointActions(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointModel,
            '@MauticPoint/SubscribedEvents/Search/global_point.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.point.actions.header.index', $results);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchPointGroup(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointGroupModel,
            '@MauticPoint/SubscribedEvents/Search/global_group.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.point.group.header.index', $results);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchPointTriggers(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pointTriggerModel,
            '@MauticPoint/SubscribedEvents/Search/global_trigger.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.point.trigger.header.index', $results);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\CommandListEvent::class)]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted('point:points:view')) {
            $event->addCommands(
                'mautic.point.actions.header.index',
                $this->pointModel->getCommandList()
            );
        }
    }
}
