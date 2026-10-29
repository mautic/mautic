<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\EventListener;

use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Service\GlobalSearch;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SearchSubscriber
{
    public function __construct(
        private MessageModel $model,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(
        event: GlobalSearchEvent::class,
        priority: 0
    )]
    public function onGlobalSearch(GlobalSearchEvent $event): void
    {
        $results = $this->globalSearch->performSearch(
            new GlobalSearchFilterDTO($event->getSearchString()),
            $this->model,
            '@MauticChannel/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.messages.header', $results);
        }
    }
}
