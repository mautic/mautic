<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class GroupScoreSubscriber
{
    public function __construct(
        private TriggerModel $triggerModel,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: GroupScoreChangeEvent::class)]
    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->triggerModel->triggerEvents($event->getContact());
    }
}
