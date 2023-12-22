<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Model\TableModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template S of object
 */
abstract class AbstractCountryTableController extends AbstractController
{
    /**
     * @var TableModelInterface<S>
     */
    protected TableModelInterface $model;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<string, array<int, array<string, int|string>>>
     */
    abstract public function getData($entity, \DateTimeInterface $dateFromObject = null, \DateTimeInterface $dateToObject = null): array;

    /**
     * @template T
     *
     * @param T $entity
     */
    abstract public function hasAccess(CorePermissions $security, $entity): bool;

    /**
     * @throws \Exception
     */
    public function viewAction(
        CorePermissions $security,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = ''
    ): Response {
        $entity = $this->model->getEntity($objectId);

        if (empty($entity) || !$this->hasAccess($security, $entity)) {
            throw new AccessDeniedHttpException();
        }

        $statsCountries = $this->getData($entity);

        return $this->render(
            '@MauticCore/Helper/geotable.html.twig',
            [
                'data'           => $statsCountries,
                'object'         => $entity,
            ]
        );
    }

    public function exportAction(int $objectId, string $format = 'csv')
    {
        $campaign = $this->model->getEntity($objectId);

        if (null === $campaign) {
            return 'test';
        }

        /*  if (!$this->hasAccess(
              'form:forms:viewown',
              'form:forms:viewother',
              $campaign->getCreatedBy()
          )
          ) {
              return $this->accessDenied();
          }*/

        return $this->model->exportResults($campaign, $format);
    }
}
