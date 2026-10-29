<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;

final readonly class SearchSubscriber
{
    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[AsEventListener(event: GlobalSearchEvent::class)]
    public function onGlobalSearch(GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $results   = $this->globalSearch->performSearch(
            $filterDTO,
            $this->dynamicContentModel,
            '@MauticDynamicContent/SubscribedEvents/Search/global.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.dynamicContent.dynamicContent', $results);
        }
    }
}
