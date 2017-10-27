<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\EventListener;

use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Model\SchedulerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSchedulerSubscriber implements EventSubscriberInterface
{
    /**
     * @var SchedulerModel
     */
    private $schedulerModel;

    public function __construct(SchedulerModel $schedulerModel)
    {
        $this->schedulerModel = $schedulerModel;
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

        $this->schedulerModel->computeScheduler($report);

        return $event;
    }
}
