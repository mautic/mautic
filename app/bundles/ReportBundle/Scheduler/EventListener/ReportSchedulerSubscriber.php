<?php

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ReportSchedulerSubscriber
{
    public function __construct(
        private SchedulerPlanner $schedulerPlanner,
    ) {
    }

    #[AsEventListener(
        event: ReportEvents::REPORT_POST_SAVE,
        priority: 0
    )]
    public function onReportSave(ReportEvent $event): void
    {
        $report = $event->getReport();

        $this->schedulerPlanner->computeScheduler($report);
    }
}
