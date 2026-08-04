<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

final class ScheduleController extends CommonAjaxController
{
    private ReportModel $reportModel;
    private DateBuilder $dateBuilder;

    #[Required]
    public function autowireScheduleController(
        ReportModel $reportModel,
    ): void {
        $this->reportModel = $reportModel;
    }

    public function indexAction($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency): JsonResponse
    {
        $dates = $this->dateBuilder->getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);

        $html = $this->render(
            '@MauticReport/Schedule/index.html.twig',
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
     */
    public function nowAction($reportId): JsonResponse
    {
        /** @var \Mautic\ReportBundle\Entity\Report $report */
        $report = $this->reportModel->getEntity($reportId);

        $security = $this->security;

        if (empty($report)) {
            $this->addFlashMessage('mautic.report.notfound', ['%id%' => $reportId], FlashBag::LEVEL_ERROR, 'messages');

            return $this->flushFlash();
        }

        if (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
            $this->addFlashMessage('mautic.core.error.accessdenied', [], FlashBag::LEVEL_ERROR);

            return $this->flushFlash();
        }

        if ($report->isScheduled()) {
            $this->addFlashMessage('mautic.report.scheduled.already', ['%id%' => $reportId], FlashBag::LEVEL_ERROR);

            return $this->flushFlash();
        }

        $report->setAsScheduledNow($this->user->getEmail());
        $this->reportModel->saveEntity($report);

        $this->addFlashMessage(
            'mautic.report.scheduled.to.now',
            ['%id%' => $reportId, '%email%' => $this->user->getEmail()]
        );

        return $this->flushFlash();
    }

    private function flushFlash(): JsonResponse
    {
        return new JsonResponse(['flashes' => $this->getFlashContent()]);
    }

    #[Required]
    public function autowire(
        DateBuilder $dateBuilder,
    ): void {
        $this->dateBuilder = $dateBuilder;
    }
}
