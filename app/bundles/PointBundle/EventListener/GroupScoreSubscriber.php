<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\TriggerModel;
use Mautic\PointBundle\PointGroupEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupScoreSubscriber implements EventSubscriberInterface
{
    private TriggerModel $triggerModel;

    public function __construct(TriggerModel $triggerModel)
    {
        $this->triggerModel = $triggerModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointGroupEvents::SCORE_CHANGE     => ['onGroupScoreChange', 0],
        ];
    }

    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->triggerModel->triggerEvents($event->getContact());
    }
}
