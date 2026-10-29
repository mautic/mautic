<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Service\GlobalSearch;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class SearchSubscriber
{
    public function __construct(
        private NotificationModel $model,
        private GlobalSearch $globalSearch,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchWebNotification(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $filterDTO->setFilters([
            'where'  => [
                [
                    'expr' => 'eq',
                    'col'  => 'mobile',
                    'val'  => 0,
                ],
            ],
        ]);
        $results = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticNotification/SubscribedEvents/Search/global-web.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.notification.notification.header', $results);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: MauticEvents\GlobalSearchEvent::class)]
    public function onGlobalSearchMobileNotification(MauticEvents\GlobalSearchEvent $event): void
    {
        $filterDTO = new GlobalSearchFilterDTO($event->getSearchString());
        $filterDTO->setFilters([
            'where'  => [
                [
                    'expr' => 'eq',
                    'col'  => 'mobile',
                    'val'  => 1,
                ],
            ],
        ]);
        $results = $this->globalSearch->performSearch(
            $filterDTO,
            $this->model,
            '@MauticNotification/SubscribedEvents/Search/global-mobile.html.twig'
        );

        if ([] !== $results) {
            $event->addResults('mautic.notification.mobile_notification.header', $results);
        }
    }
}
