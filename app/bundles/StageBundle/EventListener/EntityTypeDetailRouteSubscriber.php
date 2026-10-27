<?php

declare(strict_types=1);

namespace Mautic\StageBundle\EventListener;

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
        $event->addRoute('stage', new DetailRoute(
            'mautic_stage_action',
            'objectId',
            ['objectAction' => 'edit']
        ));
    }
}
