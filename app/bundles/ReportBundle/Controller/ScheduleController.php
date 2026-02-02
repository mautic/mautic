<?php

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Model\ScheduleModel;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends CommonAjaxController
{
    public function indexAction(DateBuilder $dateBuilder, $isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency): JsonResponse
    {
        $dates = $dateBuilder->getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);

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
        /** @var \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->getModel('report');

        /** @var \Mautic\ReportBundle\Entity\Report $report */
        $report = $model->getEntity($reportId);

        /** @var \Mautic\CoreBundle\Security\Permissions\CorePermissions $security */
        $security = $this->security;

        if (empty($report)) {
            $this->addFlashMessage('mautic.report.notfound', ['%id%' => $reportId], FlashBag::LEVEL_ERROR, 'messages');

            return $this->flushFlash(Response::HTTP_NOT_FOUND);
        }

        if (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
            $this->addFlashMessage('mautic.core.error.accessdenied', [], FlashBag::LEVEL_ERROR);

            return $this->flushFlash(Response::HTTP_FORBIDDEN);
        }

        if ($report->isScheduled()) {
            $this->addFlashMessage('mautic.report.scheduled.already', ['%id%' => $reportId], FlashBag::LEVEL_ERROR);

            return $this->flushFlash(Response::HTTP_CONFLICT);
        }

        $report->setAsScheduledNow($this->user->getEmail());
        $model->saveEntity($report);

        $this->addFlashMessage(
            'mautic.report.scheduled.to.now',
            ['%id%' => $reportId, '%email%' => $this->user->getEmail()]
        );

        return $this->flushFlash(Response::HTTP_OK);
    }

    public function exportAction(int $reportId): JsonResponse
    {
        $model    = $this->getModel('report');
        $report   = $model->getEntity($reportId);
        $security = $this->container->get('mautic.security');

        if (empty($report)) {
            $this->addFlash('mautic.report.notfound', ['%id%' => $reportId]);

            return $this->flushFlash(Response::HTTP_NOT_FOUND);
        }

        if (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $report->getCreatedBy())) {
            $this->addFlash('mautic.core.error.accessdenied', []);

            return $this->flushFlash(Response::HTTP_FORBIDDEN);
        }

        $session  = $this->getCurrentRequest()->getSession();
        $fromDate = $session->get('mautic.report.date.from', (new \DateTime('-30 days'))->format('Y-m-d'));
        $toDate   = $session->get('mautic.report.date.to', (new \DateTime())->format('Y-m-d'));

        $options                         = ['dateFrom' => $fromDate, 'dateTo' => $toDate];
        $dynamicFilters                  = $session->get('mautic.report.'.$reportId.'.filters', []);
        $options['dynamicFilters']       = $dynamicFilters;
        $options['email_to_send_report'] = $this->user->getEmail();

        $scheduleModel = $this->getModel('report.schedule_model');
        \assert($scheduleModel instanceof ScheduleModel);
        $scheduler = new Scheduler($report, new \DateTime());
        $scheduler->setData($options);
        $scheduleModel->saveEntity($scheduler);

        if ($this->dispatcher->hasListeners(ReportEvents::REPORT_SCHEDULE_EXPORT)) {
            $event = new ReportEvent($report);
            $this->dispatcher->dispatch($event, ReportEvents::REPORT_SCHEDULE_EXPORT);
        }

        $this->addFlash(
            'mautic.report.export.scheduled',
            ['%id%' => $reportId, '%email%' => $this->user->getEmail()]
        );

        return $this->flushFlash(Response::HTTP_OK);
    }

    private function flushFlash(int $status): JsonResponse
    {
        return new JsonResponse(['flashes' => $this->getFlashContent(), $status]);
    }
}
