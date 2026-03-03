<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Service\GlobalSearch;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TagModel $model,
        private GlobalSearch $globalSearch,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::GLOBAL_SEARCH => ['onGlobalSearch', 0],
        ];
    }

    public function onGlobalSearch(GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticTagManager/SubscribedEvents/Search/global.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.tagmanager.tag.header.index', $results);
        }
    }
}
