<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\TriggerModel;

final readonly class GroupScoreSubscriber
{
    public function __construct(
        private TriggerModel $triggerModel,
    ) {
    }

    #[AsEventListener(event: GroupScoreChangeEvent::class)]
    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->triggerModel->triggerEvents($event->getContact());
    }
}
