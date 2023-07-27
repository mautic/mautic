<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AbstractCountryMapController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * @extends AbstractCountryMapController<CampaignModel>
 */
class CampaignMapStatsController extends AbstractCountryMapController
{
    public const MAP_OPTIONS = [
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
    public function getData($entity, \DateTime $dateFromObject, \DateTime $dateToObject): array
    {
        return $this->model->getEmailsCountryStats(
            $entity,
            $dateFromObject,
            $dateToObject
        );
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

    /**
     * @return array<string,array<string, string>>
     */
    public function getMapOptions(): array
    {
        return self::MAP_OPTIONS;
    }
}
