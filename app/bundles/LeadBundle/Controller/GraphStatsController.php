<?php

namespace Mautic\LeadBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GraphStatsController extends CommonController
{
    public function emailsTimeGraphAction(CorePermissions $security, int $leadId, string $timeUnit): Response
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead.lead');

        /** @var Lead $lead */
        $lead = $model->getEntity($leadId);
        $model->getRepository()->refetchEntity($lead);

        if (empty($lead) || !$security->hasEntityAccess(
            'lead:leads:viewown',
            'lead:leads:viewother',
            $lead->getOwner()->getId()
        )) {
            throw new AccessDeniedHttpException();
        }

        try {
            $emailTimeStats = $this->getLeadEmailTimeStats($lead, $timeUnit);
        } catch (Exception) {
            $emailTimeStats = [];
            $this->addFlashMessage(
                'Failed to load email statistics chart',
                [],
                'error'
            );
        }

        return $this->render(
            '@MauticCore/Helper/chart.html.twig',
            [
                'chartData'   => $emailTimeStats,
                'chartType'   => 'bar',
                'chartHeight' => 250,
            ]
        );
    }

    /**
     * @return array{}|array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws Exception
     */
    public function getLeadEmailTimeStats(Lead $lead, string $timeUnit): array
    {
        $stats = [];

        switch ($timeUnit) {
            case 'd':
                $stats  = $this->getEmailDaysData($lead);
                break;
            case 'h':
                $stats = $this->getEmailHoursData($lead);
                break;
        }

        return $stats;
    }

    /**
     * @return array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws Exception
     */
    protected function getEmailDaysData(Lead $lead): array
    {
        /** @var EmailModel $model */
        $model          = $this->getModel('email');
        $statRepository = $model->getStatRepository();
        $translator     = $this->translator;

        $stats       = $statRepository->getEmailDayStats($lead);

        $chart  = new BarChart([
            $translator->trans('mautic.core.date.monday'),
            $translator->trans('mautic.core.date.tuesday'),
            $translator->trans('mautic.core.date.wednesday'),
            $translator->trans('mautic.core.date.thursday'),
            $translator->trans('mautic.core.date.friday'),
            $translator->trans('mautic.core.date.saturday'),
            $translator->trans('mautic.core.date.sunday'),
        ]);

        $chart->setDataset($translator->trans('mautic.email.sent'), array_column($stats, 'sent_count'));
        $chart->setDataset($translator->trans('mautic.email.read'), array_column($stats, 'read_count'));
        $chart->setDataset($translator->trans('mautic.email.click'), array_column($stats, 'hit_count'));

        return $chart->render();
    }

    /**
     * @return array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws Exception
     */
    protected function getEmailHoursData(Lead $lead): array
    {
        /** @var EmailModel $model */
        $model          = $this->getModel('email');
        $statRepository = $model->getStatRepository();
        $translator     = $this->translator;

        $stats = $statRepository->getEmailTimeStats($lead);

        $hoursRange = range(0, 23, 1);
        $labels     = [];

        foreach ($hoursRange as $r) {
            $labels[] = sprintf('%02d:00', $r).'-'.sprintf('%02d:00', fmod($r + 1, 24));
        }

        $chart  = new BarChart($labels);
        $chart->setDataset($translator->trans('mautic.email.sent'), array_column($stats, 'sent_count'));
        $chart->setDataset($translator->trans('mautic.email.read'), array_column($stats, 'read_count'));
        $chart->setDataset($translator->trans('mautic.email.click'), array_column($stats, 'hit_count'));

        return $chart->render();
    }
}
