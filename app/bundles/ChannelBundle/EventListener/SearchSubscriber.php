<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Service\GlobalSearch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageModel $model,
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
        $results = $this->globalSearch->performSearch(
            new GlobalSearchFilterDTO($event->getSearchString()),
            $this->model,
            '@MauticChannel/SubscribedEvents/Search/global.html.twig'
        );

        if (!empty($results)) {
            $event->addResults('mautic.messages.header', $results);
        }
    }
}
