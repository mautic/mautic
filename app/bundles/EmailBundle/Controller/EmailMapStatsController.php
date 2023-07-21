<?php

namespace Mautic\EmailBundle\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Controller\AbstractCountryMapController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends AbstractCountryMapController<EmailModel>
 */
class EmailMapStatsController extends AbstractCountryMapController
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
    protected function getData(Request $request, $entity, \DateTime $dateFromObject, \DateTime $dateToObject): array
    {
        // get A/B test information
        $parent = $entity->getVariantParent();

        // get translation parent
        $translationParent = $entity->getTranslationParent();

        $includeVariants = (($entity->isVariant() && empty($parent)) || ($entity->isTranslation() && empty($translationParent)));

        return $this->model->getEmailCountryStats(
            $entity,
            $includeVariants,
            $dateFromObject,
            $dateToObject
        );
    }
}
