<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractMapController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;

class EmailMapStatsController extends AbstractMapController
{
    public function __construct(EmailModel $model)
    {
        $this->model = $model;
    }

    protected function getEntity($objectId): ?Email
    {
        return $this->model->getEntity($objectId);
    }

    protected function getData($request, $entity, $dateFromObject, $dateToObject): array
    {
        // get A/B test information
        [$parent, $children] = $entity->getVariants();

        // get related translations
        [$translationParent, $translationChildren] = $entity->getTranslations();

        if ($chartStatsSource = $request->query->get('stats', false)) {
            $includeVariants = ('all' === $chartStatsSource);
        } else {
            $includeVariants = (($entity->isVariant() && $parent === $entity) || ($entity->isTranslation() && $translationParent === $entity));
        }

        return $this->model->getEmailCountryStats(
            $entity,
            $includeVariants,
            $dateFromObject,
            $dateToObject
        );
    }
}
