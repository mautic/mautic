<?php

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;

final readonly class ReportSchedulerSubscriber
{
    public function __construct(
        private SchedulerPlanner $schedulerPlanner,
    ) {
    }

    #[AsEventListener(event: ReportEvents::REPORT_POST_SAVE)]
    public function onReportSave(ReportEvent $event): void
    {
        $report = $event->getReport();

        $this->schedulerPlanner->computeScheduler($report);
    }
}
