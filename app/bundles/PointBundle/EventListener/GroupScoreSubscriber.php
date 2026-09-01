<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class GroupScoreSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TriggerModel $triggerModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GroupScoreChangeEvent::class => ['onGroupScoreChange', 0],
        ];
    }

    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->triggerModel->triggerEvents($event->getContact());
    }
}
