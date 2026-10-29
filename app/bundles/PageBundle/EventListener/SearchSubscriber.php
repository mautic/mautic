<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SearchSubscriber
{
    public function __construct(
        private PageModel $pageModel,
        private CorePermissions $security,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(
        event: MauticEvents\GlobalSearchEvent::class,
        priority: 0
    )]
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->pageModel,
            '@MauticPage/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.page.pages', $results);
        }
    }

    #[AsEventListener(
        event: MauticEvents\CommandListEvent::class,
        priority: 0
    )]
    public function onBuildCommandList(MauticEvents\CommandListEvent $event): void
    {
        if ($this->security->isGranted(['page:pages:viewown', 'page:pages:viewother'], 'MATCH_ONE')) {
            $event->addCommands(
                'mautic.page.pages',
                $this->pageModel->getCommandList()
            );
        }
    }
}
