<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractCountryTableController;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;

/**
 * @extends AbstractCountryTableController<Campaign>
 */
class CampaignTableStatsController extends AbstractCountryTableController
{
    public function __construct(
        CampaignModel $model,
        protected CorePermissions $security,
        protected ExportHelper $exportHelper,
        protected Translator $translator
    ) {
        $this->model = $model;
    }

    /**
     * @param Campaign $entity
     *
     * @return array<int|string, array<int|string, int|string>>
     *
     * @throws Exception
     */
    public function getData($entity): array
    {
        return $this->model->getCountryStats($entity);
    }

    /**
     * @param Campaign $entity
     */
    public function hasAccess($entity): bool
    {
        return $this->security->hasEntityAccess(
            'campaign:campaigns:viewown',
            'campaign:campaigns:viewother',
            $entity->getCreatedBy()
        );
    }

    /**
     * @param Campaign $entity
     *
     * @return array<int, string>
     */
    public function getExportHeader($entity): array
    {
        $headers = [
            $this->translator->trans('mautic.lead.lead.thead.country'),
            $this->translator->trans('mautic.lead.leads'),
        ];

        if ($entity->isEmailCampaign()) {
            array_push($headers,
                $this->translator->trans('mautic.email.graph.line.stats.sent'),
                $this->translator->trans('mautic.email.graph.line.stats.read'),
                $this->translator->trans('mautic.email.clicked')
            );
        }

        return $headers;
    }
}
