<?php

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSchedulerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SchedulerPlanner $schedulerPlanner,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ReportEvents::REPORT_POST_SAVE => ['onReportSave', 0]];
    }

    public function onReportSave(ReportEvent $event): ReportEvent
    {
        $report = $event->getReport();

        $this->schedulerPlanner->computeScheduler($report);

        return $event;
    }
}
