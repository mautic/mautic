<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Service\GlobalSearch;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SearchSubscriber
{
    public function __construct(
        private TagModel $model,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(
        event: GlobalSearchEvent::class,
    )]
    public function onGlobalSearch(GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticTagManager/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.tagmanager.tag.header.index', $results);
        }
    }
}
