<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\InsightModel;
use Mautic\PointBundle\PointGroupEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PointInsightSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private InsightModel $insightModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PointGroupEvents::SCORE_CHANGE => ['onGroupScoreChange', 0],
        ];
    }

    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->insightModel->executePointInsights($event->getContact(), $event->getGroup());
    }
}
