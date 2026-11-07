<?php

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ReportSchedulerSubscriber
{
    public function __construct(
        private SchedulerPlanner $schedulerPlanner,
    ) {
    }

    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onReportSave(EntityPostSave $event): void
    {
        $report = $event->getEntity();
        if (!$report instanceof Report) {
            return;
        }

        $this->schedulerPlanner->computeScheduler($report);
    }
}
