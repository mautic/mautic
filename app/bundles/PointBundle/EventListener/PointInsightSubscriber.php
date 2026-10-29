<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\PointBundle\Event\GroupScoreChangeEvent;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PointInsightSubscriber
{
    public function __construct(
        private InsightModel $insightModel,
    ) {
    }

    #[AsEventListener(
        event: GroupScoreChangeEvent::class,
        priority: 0
    )]
    public function onGroupScoreChange(GroupScoreChangeEvent $event): void
    {
        $this->insightModel->executePointInsights($event->getContact(), $event->getGroup());
    }
}
