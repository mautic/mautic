<?php

declare(strict_types=1);

namespace Mautic\StageBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ProjectBundle\DTO\DetailRoute;
use Mautic\ProjectBundle\Event\EntityTypeDetailRouteEvent;

final class EntityTypeDetailRouteSubscriber
{
    #[AsEventListener(event: EntityTypeDetailRouteEvent::class)]
    public function onEntityTypeDetailRoute(EntityTypeDetailRouteEvent $event): void
    {
        $event->addRoute('stage', new DetailRoute(
            'mautic_stage_action',
            'objectId',
            ['objectAction' => 'edit']
        ));
    }
}
