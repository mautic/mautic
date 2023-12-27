<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Controller\AbstractCountryTableController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * @extends AbstractCountryTableController<Email>
 */
class EmailTableStatsController extends AbstractCountryTableController
{
    public function __construct(EmailModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Email $entity
     *
     * @return array<int|string, array<int|string, int|string>>
     *
     * @throws Exception
     */
    public function getData($entity): array
    {
        // get A/B test information
        $parent = $entity->getVariantParent();

        // get translation parent
        $translationParent = $entity->getTranslationParent();

        $includeVariants = (($entity->isVariant() && empty($parent)) || ($entity->isTranslation() && empty($translationParent)));

        return $this->model->getCountryStats(
            $entity,
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
}
