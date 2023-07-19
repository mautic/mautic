<?php

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractMapController;

class CampaignMapStatsController extends AbstractMapController
{
    public function __construct(CampaignModel $model)
    {
        $this->model = $model;
    }

    protected function getEntity($objectId): ?Campaign
    {
        return $this->model->getEntity($objectId);
    }

    protected function getData($request, $entity, $dateFromObject, $dateToObject): array
    {
        return $this->model->getEmailsCountryStats(
            $entity,
            $dateFromObject,
            $dateToObject
        );
    }
}
