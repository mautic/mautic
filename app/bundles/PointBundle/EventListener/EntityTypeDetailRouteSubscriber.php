<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\ProjectBundle\DTO\DetailRoute;
use Mautic\ProjectBundle\Event\EntityTypeDetailRouteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EntityTypeDetailRouteSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EntityTypeDetailRouteEvent::class => 'onEntityTypeDetailRoute',
        ];
    }

    public function onEntityTypeDetailRoute(EntityTypeDetailRouteEvent $event): void
    {
        // Point entity uses edit
        $event->addRoute('point', new DetailRoute(
            'mautic_point_action',
            'objectId',
            ['objectAction' => 'edit']
        ));
    }
}
