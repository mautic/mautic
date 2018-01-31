<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;

/**
 * Class ScheduleController.
 */
class ScheduleController extends CommonAjaxController
{
    public function indexAction($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency)
    {
        /* @var DateBuilder $dateBuilder */
        $dateBuilder = $this->container->get('mautic.report.model.scheduler_date_builder');
        $dates       = $dateBuilder->getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);

        $html = $this->render(
            'MauticReportBundle:Schedule:index.html.php',
            [
                'dates' => $dates,
            ]
        )->getContent();

        return $this->sendJsonResponse(
            [
                'html' => $html,
            ]
        );
    }
}
