<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractCountryTableController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * @extends AbstractCountryTableController<Campaign>
 */
class CampaignTableStatsController extends AbstractCountryTableController
{
    public function __construct(CampaignModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Campaign $entity
     *
     * @return array<string, array<int, array<string, int|string>>>
     *
     * @throws Exception
     */
    public function getData($entity, \DateTimeInterface $dateFromObject = null, \DateTimeInterface $dateToObject = null): array
    {
        return $this->model->getCountryStats($entity);
    }

    /**
     * @param Campaign $entity
     */
    public function hasAccess(CorePermissions $security, $entity): bool
    {
        return $security->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $entity->getCreatedBy()
        );
    }
}
