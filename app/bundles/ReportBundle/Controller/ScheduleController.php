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
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends CommonAjaxController
{
    public function indexAction($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency)
    {
        /** @var DateBuilder $dateBuilder */
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

    /**
     * Sets report to schedule NOW if possible.
     *
     * @param int $reportId
     *
     * @return JsonResponse
     */
    public function nowAction($reportId)
    {
        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        /** @var \Mautic\ReportBundle\Entity\Report $report */
        $report = $model->getEntity($reportId);

        /** @var \Mautic\CoreBundle\Security\Permissions\CorePermissions $security */
        $security = $this->container->get('mautic.security');

        if (empty($report)) {
            $this->addFlash('mautic.report.notfound', ['%id%' => $reportId], FlashBag::LEVEL_ERROR, 'messages');

            return $this->flushFlash(Response::HTTP_NOT_FOUND);
        }

        if (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
            $this->addFlash('mautic.core.error.accessdenied', [], FlashBag::LEVEL_ERROR);

            return $this->flushFlash(Response::HTTP_FORBIDDEN);
        }

        if ($report->isScheduled()) {
            $this->addFlash('mautic.report.scheduled.already', ['%id%' => $reportId], FlashBag::LEVEL_ERROR);

            return $this->flushFlash(Response::HTTP_PROCESSING);
        }

        $report->setAsScheduledNow($this->user->getEmail());
        $model->saveEntity($report);

        $this->addFlash(
            'mautic.report.scheduled.to.now',
            ['%id%' => $reportId, '%email%' => $this->user->getEmail()]
        );

        return $this->flushFlash(Response::HTTP_OK);
    }

    /**
     * @param string $status
     *
     * @return JsonResponse
     */
    private function flushFlash($status)
    {
        return new JsonResponse(['flashes' => $this->getFlashContent()]);
    }
}
