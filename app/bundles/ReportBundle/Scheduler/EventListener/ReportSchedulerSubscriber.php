<?php

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSchedulerSubscriber implements EventSubscriberInterface
{
    /**
     * @var SchedulerPlanner
     */
    private $schedulerPlanner;

    public function __construct(SchedulerPlanner $schedulerPlanner)
    {
        $this->schedulerPlanner = $schedulerPlanner;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ReportEvents::REPORT_POST_SAVE => ['onReportSave', 0]];
    }

    public function onReportSave(ReportEvent $event)
    {
        $report = $event->getReport();

        $this->schedulerPlanner->computeScheduler($report);

        return $event;
    }
}
