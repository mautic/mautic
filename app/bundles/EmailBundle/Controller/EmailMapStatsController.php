<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Controller\AbstractCountryMapController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * @extends AbstractCountryMapController<EmailModel>
 */
class EmailMapStatsController extends AbstractCountryMapController
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

    public function __construct(EmailModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Email $entity
     *
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    public function getData($entity, \DateTime $dateFromObject, \DateTime $dateToObject): array
    {
        // get A/B test information
        $parent = $entity->getVariantParent();

        // get translation parent
        $translationParent = $entity->getTranslationParent();

        $includeVariants = (($entity->isVariant() && empty($parent)) || ($entity->isTranslation() && empty($translationParent)));

        return $this->model->getEmailCountryStats(
            $entity,
            $dateFromObject,
            $dateToObject,
            $includeVariants,
        );
    }

    /**
     * @param Email $entity
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
     * @return array<string, array<string, string>>
     */
    public function getMapOptions(): array
    {
        return self::MAP_OPTIONS;
    }
}
