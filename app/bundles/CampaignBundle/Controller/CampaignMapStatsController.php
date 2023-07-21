<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractCountryMapController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends AbstractCountryMapController<CampaignModel>
 */
class CampaignMapStatsController extends AbstractCountryMapController
{
    protected const MAP_OPTIONS = [
        'read_count' => [
            'label' => 'mautic.email.stat.read',
            'unit'  => 'Read',
        ],
        'clicked_through_count'=> [
            'label' => 'mautic.email.clicked',
            'unit'  => 'Click',
        ],
    ];

    public function __construct(CampaignModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Campaign $entity
     *
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    protected function getData(Request $request, $entity, \DateTime $dateFromObject, \DateTime $dateToObject): array
    {
        return $this->model->getEmailsCountryStats(
            $entity,
            $dateFromObject,
            $dateToObject
        );
    }
}
