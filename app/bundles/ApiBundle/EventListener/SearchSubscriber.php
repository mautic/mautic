<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;

final readonly class SearchSubscriber
{
    public function __construct(
        private ClientModel $apiClientModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: MauticEvents\GlobalSearchEvent::class,
        priority: 0
    )]
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->apiClientModel,
            '@MauticApi/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.api.client.menu.index', $results);
        }
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: MauticEvents\CommandListEvent::class,
        priority: 0
    )]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted('api:clients:view')) {
            $event->addCommands(
                'mautic.api.client.header.index',
                $this->apiClientModel->getCommandList()
            );
        }
    }
}
