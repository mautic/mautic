<?php

namespace Mautic\LeadBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GraphStatsController extends CommonController
{
    use LeadDetailsTrait;

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
}
