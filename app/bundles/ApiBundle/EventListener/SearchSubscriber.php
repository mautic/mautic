<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Model\ClientModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ClientModel $apiClientModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH      => ['onGlobalSearch', 0],
            CoreEvents::BUILD_COMMAND_LIST => ['onBuildCommandList', 0],
        ];
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->apiClientModel,
            '@MauticApi/SubscribedEvents/Search/global.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.api.client.menu.index', $results);
        }
    }

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
